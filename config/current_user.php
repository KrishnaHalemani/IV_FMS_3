<?php
require_once __DIR__ . '/db.php';

if (!function_exists('iv_clear_session_user_context')) {
    function iv_clear_session_user_context(): void
    {
        unset(
            $_SESSION['username'],
            $_SESSION['employee_id'],
            $_SESSION['employee_name'],
            $_SESSION['franchisee_id'],
            $_SESSION['franchisee_name']
        );
    }
}

if (!function_exists('iv_refresh_session_user_context')) {
    function iv_refresh_session_user_context(mysqli $conn, ?int $userId = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $resolvedUserId = $userId ?? (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0);
        if ($resolvedUserId <= 0) {
            iv_clear_session_user_context();
            return;
        }

        $stmt = $conn->prepare("
            SELECT
                u.username,
                e.id AS employee_id,
                e.name AS employee_name,
                e.franchisee_id,
                f.franchisee_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN franchisees f ON f.id = e.franchisee_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $resolvedUserId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            iv_clear_session_user_context();
            return;
        }

        $_SESSION['username'] = (string) ($row['username'] ?? '');

        if (!empty($row['employee_id'])) {
            $_SESSION['employee_id'] = (int) $row['employee_id'];
            $_SESSION['employee_name'] = (string) ($row['employee_name'] ?? '');
        } else {
            unset($_SESSION['employee_id'], $_SESSION['employee_name']);
        }

        if (!empty($row['franchisee_id'])) {
            $_SESSION['franchisee_id'] = (int) $row['franchisee_id'];
            $_SESSION['franchisee_name'] = (string) ($row['franchisee_name'] ?? '');
        } else {
            unset($_SESSION['franchisee_id'], $_SESSION['franchisee_name']);
        }
    }
}

if (!function_exists('iv_current_session_franchisee_id')) {
    function iv_current_session_franchisee_id(): ?int
    {
        $franchiseeId = isset($_SESSION['franchisee_id']) ? (int) $_SESSION['franchisee_id'] : 0;
        return $franchiseeId > 0 ? $franchiseeId : null;
    }
}
?>
