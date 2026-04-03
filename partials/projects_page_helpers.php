<?php
require_once __DIR__ . '/dashboard_helpers.php';

if (!function_exists('iv_project_normalized_status_expr')) {
    function iv_project_normalized_status_expr(string $alias = 'p'): string
    {
        return "LOWER(REPLACE(REPLACE(COALESCE({$alias}.project_status, ''), ' ', '_'), '-', '_'))";
    }
}

if (!function_exists('iv_project_normalized_priority_expr')) {
    function iv_project_normalized_priority_expr(string $alias = 'p'): string
    {
        return "LOWER(REPLACE(REPLACE(COALESCE({$alias}.project_priority, ''), ' ', '_'), '-', '_'))";
    }
}

if (!function_exists('iv_project_label_from_key')) {
    function iv_project_label_from_key(string $value): string
    {
        $value = trim(strtolower($value));
        if ($value === '') {
            return 'Unspecified';
        }

        return ucwords(str_replace('_', ' ', $value));
    }
}

if (!function_exists('iv_project_badge_tone')) {
    function iv_project_badge_tone(string $value, string $type = 'status'): string
    {
        $value = strtolower(trim($value));

        if ($type === 'priority') {
            return match ($value) {
                'critical' => 'danger',
                'high' => 'warning',
                'medium' => 'primary',
                'low' => 'success',
                default => 'secondary',
            };
        }

        return match ($value) {
            'completed', 'finished' => 'success',
            'active', 'in_progress', 'inprogress' => 'primary',
            'planned', 'draft' => 'info',
            'on_hold' => 'warning',
            'cancelled', 'declined' => 'secondary',
            default => 'dark',
        };
    }
}

if (!function_exists('iv_fetch_project_workspace')) {
    function iv_fetch_project_workspace(mysqli $conn, int $currentUserId, string $role, array $filters = []): array
    {
        $scopeIds = iv_dashboard_scope_ids($conn, $currentUserId, $role);
        $scopeIn = iv_dashboard_format_in_clause($scopeIds);
        $statusExpr = iv_project_normalized_status_expr('p');
        $priorityExpr = iv_project_normalized_priority_expr('p');

        $baseWhere = $role === 'master'
            ? '1=1'
            : "(p.created_by IN ($scopeIn) OR p.assigned_user_id IN ($scopeIn))";

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        $priority = strtolower(trim((string) ($filters['priority'] ?? 'all')));
        $search = trim((string) ($filters['search'] ?? ''));

        $allowedStatuses = ['all', 'draft', 'planned', 'active', 'on_hold', 'completed', 'cancelled'];
        $allowedPriorities = ['all', 'low', 'medium', 'high', 'critical'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'all';
        }

        $whereParts = [$baseWhere];
        if ($status !== 'all') {
            $whereParts[] = $statusExpr . " = '" . $conn->real_escape_string($status) . "'";
        }
        if ($priority !== 'all') {
            $whereParts[] = $priorityExpr . " = '" . $conn->real_escape_string($priority) . "'";
        }
        if ($search !== '') {
            $escapedSearch = $conn->real_escape_string($search);
            $whereParts[] = "(
                p.project_name LIKE '%{$escapedSearch}%'
                OR p.project_code LIKE '%{$escapedSearch}%'
                OR COALESCE(c.customer_name, '') LIKE '%{$escapedSearch}%'
                OR COALESCE(owner.username, '') LIKE '%{$escapedSearch}%'
                OR COALESCE(creator.username, '') LIKE '%{$escapedSearch}%'
            )";
        }

        $whereClause = implode(' AND ', $whereParts);

        $summarySql = "
            SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN {$statusExpr} IN ('active', 'planned') THEN 1 ELSE 0 END) AS active_projects,
                SUM(CASE WHEN {$statusExpr} = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
                SUM(CASE WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() AND {$statusExpr} NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) AS delayed_projects,
                SUM(CASE WHEN p.assigned_user_id IS NULL OR p.assigned_user_id = 0 THEN 1 ELSE 0 END) AS unowned_projects,
                COALESCE(SUM(p.project_hours), 0) AS planned_hours,
                COALESCE(SUM(p.estimated_budget), 0) AS portfolio_budget
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            LEFT JOIN users creator ON creator.id = p.created_by
            WHERE {$whereClause}
        ";
        $summary = $conn->query($summarySql)->fetch_assoc() ?: [];

        $rows = [];
        $projectsSql = "
            SELECT
                p.id,
                p.project_name,
                p.project_code,
                p.project_priority,
                p.project_status,
                p.project_hours,
                p.estimated_budget,
                p.start_date,
                p.end_date,
                COALESCE(c.customer_name, p.customer_name, 'Internal Project') AS client_name,
                COALESCE(owner.username, 'Unassigned') AS owner_name,
                COALESCE(owner.role, 'unassigned') AS owner_role,
                COALESCE(creator.username, 'System') AS creator_name,
                COALESCE(i.invoice_number, '-') AS invoice_number,
                COUNT(DISTINCT pt.id) AS milestone_count,
                CASE
                    WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() AND {$statusExpr} NOT IN ('completed', 'cancelled') THEN 1
                    ELSE 0
                END AS is_delayed
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            LEFT JOIN users creator ON creator.id = p.created_by
            LEFT JOIN invoices i ON i.id = p.related_invoice_id
            LEFT JOIN project_targets pt ON pt.project_id = p.id
            WHERE {$whereClause}
            GROUP BY
                p.id,
                p.project_name,
                p.project_code,
                p.project_priority,
                p.project_status,
                p.project_hours,
                p.estimated_budget,
                p.start_date,
                p.end_date,
                c.customer_name,
                p.customer_name,
                owner.username,
                owner.role,
                creator.username,
                i.invoice_number
            ORDER BY p.created_at DESC, p.id DESC
        ";
        $projectResult = $conn->query($projectsSql);
        while ($projectResult && $row = $projectResult->fetch_assoc()) {
            $rows[] = $row;
        }

        $upcomingMilestones = [];
        $milestoneSql = "
            SELECT
                p.project_name,
                pt.title,
                pt.due_date
            FROM project_targets pt
            INNER JOIN projects p ON p.id = pt.project_id
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            LEFT JOIN users creator ON creator.id = p.created_by
            WHERE {$whereClause}
              AND pt.due_date IS NOT NULL
              AND pt.due_date >= CURDATE()
            ORDER BY pt.due_date ASC
            LIMIT 5
        ";
        $milestoneResult = $conn->query($milestoneSql);
        while ($milestoneResult && $row = $milestoneResult->fetch_assoc()) {
            $upcomingMilestones[] = $row;
        }

        $statusBreakdown = [];
        $statusResult = $conn->query("
            SELECT {$statusExpr} AS status_key, COUNT(*) AS total
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            LEFT JOIN users creator ON creator.id = p.created_by
            WHERE {$whereClause}
            GROUP BY {$statusExpr}
            ORDER BY total DESC
        ");
        while ($statusResult && $row = $statusResult->fetch_assoc()) {
            $statusBreakdown[] = [
                'key' => $row['status_key'] !== '' ? (string) $row['status_key'] : 'unspecified',
                'total' => (int) $row['total'],
            ];
        }

        return [
            'summary' => [
                'total_projects' => (int) ($summary['total_projects'] ?? 0),
                'active_projects' => (int) ($summary['active_projects'] ?? 0),
                'completed_projects' => (int) ($summary['completed_projects'] ?? 0),
                'delayed_projects' => (int) ($summary['delayed_projects'] ?? 0),
                'unowned_projects' => (int) ($summary['unowned_projects'] ?? 0),
                'planned_hours' => (int) ($summary['planned_hours'] ?? 0),
                'portfolio_budget' => (float) ($summary['portfolio_budget'] ?? 0),
            ],
            'rows' => $rows,
            'upcoming_milestones' => $upcomingMilestones,
            'status_breakdown' => $statusBreakdown,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'search' => $search,
                'available_statuses' => ['all', 'draft', 'planned', 'active', 'on_hold', 'completed', 'cancelled'],
                'available_priorities' => ['all', 'low', 'medium', 'high', 'critical'],
            ],
        ];
    }
}
