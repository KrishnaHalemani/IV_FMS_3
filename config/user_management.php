<?php
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/franchise_binding.php';

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
                creator.role AS creator_role,
                f.franchisee_name,
                f.franchisee_code
            FROM users u
            LEFT JOIN users creator ON creator.id = u.created_by
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN franchisees f ON f.id = e.franchisee_id
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

if (!function_exists('createManagedUserAccount')) {
    function createManagedUserAccount(
        mysqli $conn,
        int $actorUserId,
        string $actorRole,
        string $email,
        string $username,
        string $password,
        string $requestedRole,
        ?int $franchiseeId = null
    ): array {
        $email = trim($email);
        $username = trim($username);
        $requestedRole = trim($requestedRole);

        if ($email === '' || $username === '' || $password === '') {
            return ['ok' => false, 'error' => 'Login account fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Please enter a valid login email address.'];
        }

        if (!in_array($requestedRole, getCreatableRoles($actorRole), true)) {
            return ['ok' => false, 'error' => 'You are not allowed to create that system role.'];
        }

        if (!iv_can_create_bound_role($franchiseeId, $requestedRole)) {
            return ['ok' => false, 'error' => 'A franchisee must be assigned before creating a non-master login account.'];
        }

        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Login password must be at least 6 characters long.'];
        }

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $checkStmt->bind_param("ss", $email, $username);
        $checkStmt->execute();
        $existingUser = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            return ['ok' => false, 'error' => 'Login email or username already exists.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO users (email, username, password, role, created_by) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssi", $email, $username, $hashedPassword, $requestedRole, $actorUserId);
        $ok = $insertStmt->execute();
        $userId = (int) $insertStmt->insert_id;
        $insertError = $insertStmt->error;
        $insertStmt->close();

        if (!$ok) {
            return ['ok' => false, 'error' => $insertError !== '' ? $insertError : 'Unable to create login account.'];
        }

        return ['ok' => true, 'user_id' => $userId];
    }
}
?>
