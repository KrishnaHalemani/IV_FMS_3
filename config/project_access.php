<?php
require_once __DIR__ . '/current_user.php';

if (!function_exists('iv_project_access_scope_ids')) {
    function iv_project_access_scope_ids(mysqli $conn, int $currentUserId, string $role): array
    {
        $ids = [];
        if ($currentUserId > 0) {
            $ids[] = $currentUserId;
        }

        $sql = match ($role) {
            'super' => "SELECT id FROM users WHERE created_by = ? AND role IN ('admin', 'user')",
            'admin' => "SELECT id FROM users WHERE created_by = ? AND role = 'user'",
            default => null,
        };

        if ($sql !== null && $currentUserId > 0) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }
            $stmt->close();
        }

        return array_values(array_unique(array_filter($ids)));
    }
}

if (!function_exists('iv_project_access_in_clause')) {
    function iv_project_access_in_clause(array $ids): string
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        if ($ids === []) {
            return '0';
        }

        return implode(',', $ids);
    }
}

if (!function_exists('iv_project_scope_condition')) {
    function iv_project_scope_condition(array $scopeIds, string $projectAlias = 'p'): string
    {
        $scopeIn = iv_project_access_in_clause($scopeIds);

        return "(
            {$projectAlias}.created_by IN ({$scopeIn})
            OR {$projectAlias}.assigned_user_id IN ({$scopeIn})
            OR EXISTS (
                SELECT 1
                FROM project_employees pe_scope
                INNER JOIN employees e_scope ON e_scope.id = pe_scope.employee_id
                WHERE pe_scope.project_id = {$projectAlias}.id
                  AND e_scope.user_id IN ({$scopeIn})
            )
        )";
    }
}

if (!function_exists('iv_user_can_access_project')) {
    function iv_user_can_access_project(mysqli $conn, int $projectId, int $currentUserId, string $role): bool
    {
        if ($projectId <= 0 || $currentUserId <= 0 || $role === '') {
            return false;
        }

        if ($role === 'master') {
            return true;
        }

        $scopeCondition = iv_project_scope_condition(iv_project_access_scope_ids($conn, $currentUserId, $role), 'p');
        $franchiseeId = iv_current_session_franchisee_id();
        $franchiseeClause = $franchiseeId !== null ? " AND p.franchisee_id = " . (int) $franchiseeId : '';

        $stmt = $conn->prepare("SELECT 1 FROM projects p WHERE p.id = ? AND {$scopeCondition}{$franchiseeClause} LIMIT 1");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allowed = (bool) $result->fetch_row();
        $stmt->close();

        return $allowed;
    }
}
