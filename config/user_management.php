<?php
require_once __DIR__ . '/roles.php';

if (!function_exists('getCreatableRoles')) {
    function getCreatableRoles(string $role): array
    {
        return match ($role) {
            'master' => ['super', 'admin', 'user'],
            'super' => ['admin', 'user'],
            'admin' => ['user'],
            default => [],
        };
    }
}

if (!function_exists('canManageTargetRole')) {
    function canManageTargetRole(string $actorRole, string $targetRole): bool
    {
        return getRoleLevel($actorRole) > getRoleLevel($targetRole);
    }
}

if (!function_exists('getUserManagementHeading')) {
    function getUserManagementHeading(string $role): string
    {
        return match ($role) {
            'master' => 'All superadmins, admins, and users',
            'super' => 'Admins and users created by you',
            'admin' => 'Users created by you',
            default => 'Users',
        };
    }
}

if (!function_exists('buildVisibleUsersQuery')) {
    function buildVisibleUsersQuery(string $role): array
    {
        $baseSql = "
            SELECT
                u.id,
                u.username,
                u.email,
                u.role,
                u.created_at,
                u.created_by,
                creator.username AS creator_name,
                creator.role AS creator_role
            FROM users u
            LEFT JOIN users creator ON creator.id = u.created_by
        ";

        return match ($role) {
            'master' => [
                'sql' => $baseSql . " WHERE u.role IN ('super', 'admin', 'user') ORDER BY FIELD(u.role, 'super', 'admin', 'user'), u.created_at DESC",
                'types' => '',
            ],
            'super' => [
                'sql' => $baseSql . " WHERE u.created_by = ? AND u.role IN ('admin', 'user') ORDER BY FIELD(u.role, 'admin', 'user'), u.created_at DESC",
                'types' => 'i',
            ],
            'admin' => [
                'sql' => $baseSql . " WHERE u.created_by = ? AND u.role = 'user' ORDER BY u.created_at DESC",
                'types' => 'i',
            ],
            default => [
                'sql' => $baseSql . " WHERE 1 = 0",
                'types' => '',
            ],
        };
    }
}

if (!function_exists('fetchVisibleUsers')) {
    function fetchVisibleUsers(mysqli $conn, int $currentUserId, string $currentRole): array
    {
        $query = buildVisibleUsersQuery($currentRole);
        $stmt = $conn->prepare($query['sql']);

        if ($query['types'] !== '') {
            $stmt->bind_param($query['types'], $currentUserId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();

        return $users;
    }
}

if (!function_exists('fetchAssignableUsers')) {
    function fetchAssignableUsers(mysqli $conn, int $currentUserId, string $currentRole): array
    {
        $assignableRoles = getCreatableRoles($currentRole);
        if ($assignableRoles === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($assignableRoles), '?'));
        $sql = "
            SELECT id, username, email, role, created_by
            FROM users
            WHERE role IN ($placeholders)
        ";

        if ($currentRole !== 'master') {
            $sql .= " AND created_by = ?";
        }

        $sql .= " ORDER BY FIELD(role, 'super', 'admin', 'user'), username ASC";

        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($assignableRoles));
        $params = $assignableRoles;

        if ($currentRole !== 'master') {
            $types .= 'i';
            $params[] = $currentUserId;
        }

        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();

        return $users;
    }
}

if (!function_exists('fetchAssignableUsersIndexed')) {
    function fetchAssignableUsersIndexed(mysqli $conn, int $currentUserId, string $currentRole): array
    {
        $indexed = [];

        foreach (fetchAssignableUsers($conn, $currentUserId, $currentRole) as $user) {
            $indexed[(int) $user['id']] = $user;
        }

        return $indexed;
    }
}
?>
