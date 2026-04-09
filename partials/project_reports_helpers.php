<?php
require_once __DIR__ . '/dashboard_helpers.php';
require_once __DIR__ . '/../config/project_access.php';

if (!function_exists('iv_fetch_project_report_data')) {
    function iv_fetch_project_report_data(mysqli $conn, int $currentUserId, string $role, array $filters = []): array
    {
        $scopeIds = iv_dashboard_scope_ids($conn, $currentUserId, $role);
        $scopeIn = iv_dashboard_format_in_clause($scopeIds);
        $franchiseeId = iv_current_session_franchisee_id();

        $projectScope = $role === 'master'
            ? '1=1'
            : iv_project_scope_condition($scopeIds, 'p');

        if ($role !== 'master' && $franchiseeId !== null) {
            $projectScope .= " AND p.franchisee_id = " . (int) $franchiseeId;
        }

        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $priorityFilter = trim((string) ($filters['priority'] ?? ''));

        $whereParts = [$projectScope];
        if ($statusFilter !== '') {
            $safeStatus = $conn->real_escape_string($statusFilter);
            $whereParts[] = "COALESCE(p.project_status, '') = '{$safeStatus}'";
        }
        if ($priorityFilter !== '') {
            $safePriority = $conn->real_escape_string($priorityFilter);
            $whereParts[] = "COALESCE(p.project_priority, '') = '{$safePriority}'";
        }

        $whereClause = implode(' AND ', $whereParts);

        $summarySql = "
            SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN p.project_status IN ('active', 'Active', 'In Progress', 'inprogress', 'planned') THEN 1 ELSE 0 END) AS active_projects,
                SUM(CASE WHEN p.project_status IN ('completed', 'Completed', 'Finished', 'finished') THEN 1 ELSE 0 END) AS completed_projects,
                SUM(CASE WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() AND p.project_status NOT IN ('completed', 'Completed', 'Finished', 'finished', 'cancelled', 'Declined', 'declined') THEN 1 ELSE 0 END) AS delayed_projects,
                SUM(CASE WHEN p.assigned_user_id IS NULL OR p.assigned_user_id = 0 THEN 1 ELSE 0 END) AS without_owner,
                COALESCE(SUM(p.project_hours), 0) AS total_hours,
                COALESCE(SUM(p.estimated_budget), 0) AS total_budget
            FROM projects p
            WHERE {$whereClause}
        ";
        $summary = $conn->query($summarySql)->fetch_assoc() ?: [];

        $statusBreakdown = [];
        $statusResult = $conn->query("
            SELECT COALESCE(NULLIF(p.project_status, ''), 'unspecified') AS label, COUNT(*) AS total
            FROM projects p
            WHERE {$whereClause}
            GROUP BY label
            ORDER BY total DESC
        ");
        while ($statusResult && $row = $statusResult->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }

        $priorityBreakdown = [];
        $priorityResult = $conn->query("
            SELECT COALESCE(NULLIF(p.project_priority, ''), 'unspecified') AS label, COUNT(*) AS total
            FROM projects p
            WHERE {$whereClause}
            GROUP BY label
            ORDER BY total DESC
        ");
        while ($priorityResult && $row = $priorityResult->fetch_assoc()) {
            $priorityBreakdown[] = $row;
        }

        $projects = [];
        $projectsResult = $conn->query("
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
                c.customer_name,
                i.invoice_number,
                owner.username AS owner_name,
                creator.username AS creator_name,
                COUNT(pt.id) AS milestone_count
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN invoices i ON i.id = p.related_invoice_id
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            LEFT JOIN users creator ON creator.id = p.created_by
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
                i.invoice_number,
                owner.username,
                creator.username
            ORDER BY p.created_at DESC
            LIMIT 12
        ");
        while ($projectsResult && $row = $projectsResult->fetch_assoc()) {
            $projects[] = $row;
        }

        $riskProjects = [];
        $riskResult = $conn->query("
            SELECT
                p.project_name,
                p.project_status,
                p.end_date,
                owner.username AS owner_name
            FROM projects p
            LEFT JOIN users owner ON owner.id = p.assigned_user_id
            WHERE {$projectScope}
              AND (
                    (p.end_date IS NOT NULL AND p.end_date < CURDATE() AND p.project_status NOT IN ('completed', 'Completed', 'Finished', 'finished', 'cancelled', 'Declined', 'declined'))
                    OR p.assigned_user_id IS NULL
                  )
            ORDER BY p.end_date IS NULL ASC, p.end_date ASC
            LIMIT 6
        ");
        while ($riskResult && $row = $riskResult->fetch_assoc()) {
            $riskProjects[] = $row;
        }

        $upcomingMilestones = [];
        $milestoneResult = $conn->query("
            SELECT
                p.project_name,
                pt.title,
                pt.due_date
            FROM project_targets pt
            INNER JOIN projects p ON p.id = pt.project_id
            WHERE {$projectScope}
              AND pt.due_date IS NOT NULL
              AND pt.due_date >= CURDATE()
            ORDER BY pt.due_date ASC
            LIMIT 6
        ");
        while ($milestoneResult && $row = $milestoneResult->fetch_assoc()) {
            $upcomingMilestones[] = $row;
        }

        $availableStatuses = [];
        $statusOptions = $conn->query("
            SELECT DISTINCT COALESCE(NULLIF(project_status, ''), 'unspecified') AS label
            FROM projects
            WHERE project_status IS NOT NULL
            ORDER BY label ASC
        ");
        while ($statusOptions && $row = $statusOptions->fetch_assoc()) {
            $availableStatuses[] = $row['label'];
        }

        $availablePriorities = [];
        $priorityOptions = $conn->query("
            SELECT DISTINCT COALESCE(NULLIF(project_priority, ''), 'unspecified') AS label
            FROM projects
            WHERE project_priority IS NOT NULL
            ORDER BY label ASC
        ");
        while ($priorityOptions && $row = $priorityOptions->fetch_assoc()) {
            $availablePriorities[] = $row['label'];
        }

        return [
            'summary' => [
                'total' => (int) ($summary['total_projects'] ?? 0),
                'active' => (int) ($summary['active_projects'] ?? 0),
                'completed' => (int) ($summary['completed_projects'] ?? 0),
                'delayed' => (int) ($summary['delayed_projects'] ?? 0),
                'without_owner' => (int) ($summary['without_owner'] ?? 0),
                'hours' => (int) ($summary['total_hours'] ?? 0),
                'budget' => (float) ($summary['total_budget'] ?? 0),
            ],
            'status_breakdown' => $statusBreakdown,
            'priority_breakdown' => $priorityBreakdown,
            'projects' => $projects,
            'risk_projects' => $riskProjects,
            'upcoming_milestones' => $upcomingMilestones,
            'filters' => [
                'status' => $statusFilter,
                'priority' => $priorityFilter,
                'available_statuses' => $availableStatuses,
                'available_priorities' => $availablePriorities,
            ],
        ];
    }
}
?>
