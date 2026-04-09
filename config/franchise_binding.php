<?php
require_once __DIR__ . '/db.php';

if (!function_exists('iv_user_requires_franchise_binding')) {
    function iv_user_requires_franchise_binding(string $role): bool
    {
        return $role !== '' && $role !== 'master';
    }
}

if (!function_exists('iv_user_has_franchise_binding')) {
    function iv_user_has_franchise_binding(mysqli $conn, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $conn->prepare("
            SELECT 1
            FROM employees
            WHERE user_id = ?
              AND franchisee_id IS NOT NULL
            LIMIT 1
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $bound = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $bound;
    }
}

if (!function_exists('iv_can_create_bound_role')) {
    function iv_can_create_bound_role(?int $franchiseeId, string $requestedRole): bool
    {
        if (!iv_user_requires_franchise_binding($requestedRole)) {
            return true;
        }

        return $franchiseeId !== null && $franchiseeId > 0;
    }
}
?>
