<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_management.php';
require_once __DIR__ . '/../config/activity_log.php';
require_once __DIR__ . '/../config/current_user.php';
require_once __DIR__ . '/../config/project_access.php';

if (!function_exists('iv_dashboard_format_in_clause')) {
    function iv_dashboard_format_in_clause(array $ids): string
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return '0';
        }

        return implode(',', $ids);
    }
}

if (!function_exists('iv_dashboard_role_label')) {
    function iv_dashboard_role_label(string $role): string
    {
        return match ($role) {
            'master' => 'Master Control',
            'super' => 'Superadmin Command',
            'admin' => 'Admin Operations',
            default => ucfirst($role),
        };
    }
}

if (!function_exists('iv_dashboard_team_segments')) {
    function iv_dashboard_team_segments(string $role, array $teamRow): array
    {
        return match ($role) {
            'master' => [
                ['label' => 'Superadmins', 'count' => (int) ($teamRow['super_count'] ?? 0), 'tone' => 'primary'],
                ['label' => 'Admins', 'count' => (int) ($teamRow['admin_count'] ?? 0), 'tone' => 'success'],
                ['label' => 'Users', 'count' => (int) ($teamRow['user_count'] ?? 0), 'tone' => 'warning'],
            ],
            'super' => [
                ['label' => 'Admins', 'count' => (int) ($teamRow['super_count'] ?? 0), 'tone' => 'primary'],
                ['label' => 'Users', 'count' => (int) ($teamRow['admin_count'] ?? 0), 'tone' => 'success'],
            ],
            'admin' => [
                ['label' => 'Users', 'count' => (int) ($teamRow['user_count'] ?? 0), 'tone' => 'primary'],
            ],
            default => [],
        };
    }
}

if (!function_exists('iv_dashboard_subordinate_ids')) {
    function iv_dashboard_subordinate_ids(mysqli $conn, int $currentUserId, string $role): array
    {
        if (!in_array($role, ['super', 'admin'], true)) {
            return [];
        }

        $sql = match ($role) {
            'super' => "SELECT id FROM users WHERE created_by = ? AND role IN ('admin', 'user')",
            'admin' => "SELECT id FROM users WHERE created_by = ? AND role = 'user'",
            default => "SELECT id FROM users WHERE 1 = 0",
        };

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];

        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }

        $stmt->close();

        return $ids;
    }
}

if (!function_exists('iv_dashboard_scope_ids')) {
    function iv_dashboard_scope_ids(mysqli $conn, int $currentUserId, string $role): array
    {
        $ids = [$currentUserId];
        foreach (iv_dashboard_subordinate_ids($conn, $currentUserId, $role) as $id) {
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('iv_fetch_dashboard_metrics')) {
    function iv_fetch_dashboard_metrics(mysqli $conn, int $currentUserId, string $role): array
    {
        iv_ensure_activity_log_table($conn);

        $scopeIds = iv_dashboard_scope_ids($conn, $currentUserId, $role);
        $scopeIn = iv_dashboard_format_in_clause($scopeIds);
        $subordinateIds = array_values(array_diff($scopeIds, [$currentUserId]));
        $subordinateIn = iv_dashboard_format_in_clause($subordinateIds);
        $franchiseeId = iv_current_session_franchisee_id();

        $projectScope = $role === 'master'
            ? '1=1'
            : iv_project_scope_condition($scopeIds, 'p');

        if ($role !== 'master' && $franchiseeId !== null) {
            $projectScope .= " AND p.franchisee_id = " . (int) $franchiseeId;
        }

        $activityScope = $role === 'master'
            ? '1=1'
            : "user_id IN ($scopeIn)";

        $teamSql = match ($role) {
            'master' => "
                SELECT
                    SUM(role = 'super') AS super_count,
                    SUM(role = 'admin') AS admin_count,
                    SUM(role = 'user') AS user_count,
                    COUNT(*) AS total_team
                FROM users
                WHERE role IN ('super', 'admin', 'user')
            ",
            'super' => "
                SELECT
                    SUM(role = 'admin') AS super_count,
                    SUM(role = 'user') AS admin_count,
                    0 AS user_count,
                    COUNT(*) AS total_team
                FROM users
                WHERE created_by = {$currentUserId} AND role IN ('admin', 'user')
            ",
            default => "
                SELECT
                    0 AS super_count,
                    0 AS admin_count,
                    SUM(role = 'user') AS user_count,
                    COUNT(*) AS total_team
                FROM users
                WHERE created_by = {$currentUserId} AND role = 'user'
            ",
        };

        $teamRow = $conn->query($teamSql)->fetch_assoc() ?: [
            'super_count' => 0,
            'admin_count' => 0,
            'user_count' => 0,
            'total_team' => 0,
        ];

        $projectSql = "
            SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN p.project_status IN ('active', 'Active', 'In Progress', 'inprogress', 'planned') THEN 1 ELSE 0 END) AS active_projects,
                SUM(CASE WHEN p.project_status IN ('completed', 'Completed', 'Finished', 'finished') THEN 1 ELSE 0 END) AS completed_projects,
                SUM(CASE WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() AND p.project_status NOT IN ('completed', 'Completed', 'Finished', 'finished', 'cancelled', 'Declined', 'declined') THEN 1 ELSE 0 END) AS delayed_projects,
                SUM(CASE WHEN p.assigned_user_id IS NULL OR p.assigned_user_id = 0 THEN 1 ELSE 0 END) AS projects_without_owner,
                SUM(CASE WHEN p.customer_id IS NULL THEN 1 ELSE 0 END) AS unlinked_clients,
                COALESCE(SUM(p.estimated_budget), 0) AS portfolio_value
            FROM projects p
            WHERE {$projectScope}
        ";
        $projectRow = $conn->query($projectSql)->fetch_assoc() ?: [];

        $statusResult = $conn->query("
            SELECT COALESCE(NULLIF(p.project_status, ''), 'unspecified') AS status_label, COUNT(*) AS total
            FROM projects p
            WHERE {$projectScope}
            GROUP BY status_label
            ORDER BY total DESC
            LIMIT 6
        ");
        $statusBreakdown = [];
        while ($statusResult && $row = $statusResult->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }

        $milestoneSql = "
            SELECT
                COUNT(DISTINCT p.id) AS projects_without_milestones,
                COUNT(pt.id) AS total_milestones
            FROM projects p
            LEFT JOIN project_targets pt ON pt.project_id = p.id
            WHERE {$projectScope}
        ";
        $milestoneRow = $conn->query($milestoneSql)->fetch_assoc() ?: [];

        $projectsWithoutMilestones = 0;
        $withoutMilestoneResult = $conn->query("
            SELECT COUNT(*) AS total
            FROM projects p
            LEFT JOIN project_targets pt ON pt.project_id = p.id
            WHERE {$projectScope}
            GROUP BY p.id
            HAVING COUNT(pt.id) = 0
        ");
        if ($withoutMilestoneResult) {
            $projectsWithoutMilestones = $withoutMilestoneResult->num_rows;
        }

        $imsSql = $role === 'master'
            ? "
                SELECT COUNT(*) AS total_invoices, COALESCE(SUM(grand_total), 0) AS total_collected
                FROM invoices
            "
            : "
                SELECT COUNT(*) AS total_invoices, COALESCE(SUM(i.grand_total), 0) AS total_collected
                FROM invoices i
                WHERE i.id IN (
                    SELECT DISTINCT p.related_invoice_id
                    FROM projects p
                    WHERE {$projectScope} AND p.related_invoice_id IS NOT NULL
                )
            ";
        $imsRow = $conn->query($imsSql)->fetch_assoc() ?: [];

        $linkedClientsSql = $role === 'master'
            ? "SELECT COUNT(*) AS total_clients FROM customers"
            : "
                SELECT COUNT(DISTINCT c.id) AS total_clients
                FROM customers c
                INNER JOIN projects p ON p.customer_id = c.id
                WHERE {$projectScope}
            ";
        $clientRow = $conn->query($linkedClientsSql)->fetch_assoc() ?: [];

        $upcomingMilestones = [];
        $upcomingResult = $conn->query("
            SELECT p.project_name, pt.title, pt.due_date
            FROM project_targets pt
            INNER JOIN projects p ON p.id = pt.project_id
            WHERE {$projectScope}
              AND pt.due_date IS NOT NULL
              AND pt.due_date >= CURDATE()
            ORDER BY pt.due_date ASC
            LIMIT 5
        ");
        while ($upcomingResult && $row = $upcomingResult->fetch_assoc()) {
            $upcomingMilestones[] = $row;
        }

        $recentActivity = [];
        $activityResult = $conn->query("
            SELECT user_role, user_email, action, details, created_at
            FROM activity_logs
            WHERE {$activityScope}
            ORDER BY created_at DESC
            LIMIT 8
        ");
        while ($activityResult && $row = $activityResult->fetch_assoc()) {
            $recentActivity[] = $row;
        }

        $alerts = [];
        if ((int) ($projectRow['delayed_projects'] ?? 0) > 0) {
            $alerts[] = [
                'tone' => 'danger',
                'title' => 'Delayed projects need attention',
                'value' => (int) $projectRow['delayed_projects'],
            ];
        }
        if ((int) ($projectRow['projects_without_owner'] ?? 0) > 0) {
            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Projects without owner',
                'value' => (int) $projectRow['projects_without_owner'],
            ];
        }
        if ($projectsWithoutMilestones > 0) {
            $alerts[] = [
                'tone' => 'info',
                'title' => 'Projects without milestones',
                'value' => $projectsWithoutMilestones,
            ];
        }
        if ((int) ($projectRow['unlinked_clients'] ?? 0) > 0 && $role === 'master') {
            $alerts[] = [
                'tone' => 'secondary',
                'title' => 'Projects missing client link',
                'value' => (int) $projectRow['unlinked_clients'],
            ];
        }

        $franchiseRow = [
            'total_franchisees' => 0,
            'active_franchisees' => 0,
            'franchise_project_count' => 0,
            'franchise_employee_count' => 0,
            'franchise_portfolio_value' => 0,
            'franchise_revenue_value' => 0,
        ];
        $franchiseTable = [];

        if ($role === 'master') {
            $franchiseSql = "
                SELECT
                    (SELECT COUNT(*) FROM franchisees) AS total_franchisees,
                    (SELECT COUNT(*) FROM franchisees WHERE status = 'Active') AS active_franchisees,
                    (SELECT COUNT(*) FROM projects WHERE franchisee_id IS NOT NULL) AS franchise_project_count,
                    (SELECT COUNT(*) FROM employees WHERE franchisee_id IS NOT NULL) AS franchise_employee_count,
                    (SELECT COALESCE(SUM(estimated_budget), 0) FROM projects WHERE franchisee_id IS NOT NULL) AS franchise_portfolio_value,
                    (
                        SELECT COALESCE(SUM(i.grand_total), 0)
                        FROM invoices i
                        WHERE i.id IN (
                            SELECT DISTINCT p.related_invoice_id
                            FROM projects p
                            WHERE p.franchisee_id IS NOT NULL
                              AND p.related_invoice_id IS NOT NULL
                        )
                    ) AS franchise_revenue_value
            ";
            $franchiseRow = $conn->query($franchiseSql)->fetch_assoc() ?: $franchiseRow;

            $franchiseTableResult = $conn->query("
                SELECT
                    f.id,
                    f.franchisee_name,
                    f.franchisee_code,
                    f.status,
                    (
                        SELECT COUNT(*)
                        FROM projects p
                        WHERE p.franchisee_id = f.id
                    ) AS total_projects,
                    (
                        SELECT COUNT(*)
                        FROM projects p
                        WHERE p.franchisee_id = f.id
                          AND p.project_status IN ('In Progress', 'Not Started', 'On Hold', 'active', 'Active', 'planned')
                    ) AS active_projects,
                    (
                        SELECT COUNT(*)
                        FROM employees e
                        WHERE e.franchisee_id = f.id
                    ) AS total_employees,
                    (
                        SELECT COALESCE(SUM(p.estimated_budget), 0)
                        FROM projects p
                        WHERE p.franchisee_id = f.id
                    ) AS portfolio_value,
                    (
                        SELECT COALESCE(SUM(i.grand_total), 0)
                        FROM invoices i
                        WHERE i.id IN (
                            SELECT DISTINCT p.related_invoice_id
                            FROM projects p
                            WHERE p.franchisee_id = f.id
                              AND p.related_invoice_id IS NOT NULL
                        )
                    ) AS revenue_value
                FROM franchisees f
                ORDER BY portfolio_value DESC, total_projects DESC, f.franchisee_name ASC
                LIMIT 6
            ");

            while ($franchiseTableResult && $row = $franchiseTableResult->fetch_assoc()) {
                $franchiseTable[] = $row;
            }
        }

        return [
            'role_label' => iv_dashboard_role_label($role),
            'scope_ids' => $scopeIds,
            'team' => [
                'super' => (int) ($teamRow['super_count'] ?? 0),
                'admin' => (int) ($teamRow['admin_count'] ?? 0),
                'user' => (int) ($teamRow['user_count'] ?? 0),
                'total' => (int) ($teamRow['total_team'] ?? 0),
                'segments' => iv_dashboard_team_segments($role, $teamRow),
            ],
            'projects' => [
                'total' => (int) ($projectRow['total_projects'] ?? 0),
                'active' => (int) ($projectRow['active_projects'] ?? 0),
                'completed' => (int) ($projectRow['completed_projects'] ?? 0),
                'delayed' => (int) ($projectRow['delayed_projects'] ?? 0),
                'without_owner' => (int) ($projectRow['projects_without_owner'] ?? 0),
                'without_milestones' => $projectsWithoutMilestones,
                'portfolio_value' => (float) ($projectRow['portfolio_value'] ?? 0),
            ],
            'ims' => [
                'total_invoices' => (int) ($imsRow['total_invoices'] ?? 0),
                'total_collected' => (float) ($imsRow['total_collected'] ?? 0),
            ],
            'clients' => [
                'total' => (int) ($clientRow['total_clients'] ?? 0),
            ],
            'status_breakdown' => $statusBreakdown,
            'upcoming_milestones' => $upcomingMilestones,
            'recent_activity' => $recentActivity,
            'alerts' => $alerts,
            'franchisees' => [
                'total' => (int) ($franchiseRow['total_franchisees'] ?? 0),
                'active' => (int) ($franchiseRow['active_franchisees'] ?? 0),
                'projects' => (int) ($franchiseRow['franchise_project_count'] ?? 0),
                'employees' => (int) ($franchiseRow['franchise_employee_count'] ?? 0),
                'portfolio_value' => (float) ($franchiseRow['franchise_portfolio_value'] ?? 0),
                'revenue_value' => (float) ($franchiseRow['franchise_revenue_value'] ?? 0),
                'table' => $franchiseTable,
            ],
        ];
    }
}
?>
