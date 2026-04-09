<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';

if (!function_exists('iv_require_master_session')) {
    function iv_require_master_session(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            header('Location: login.php');
            exit;
        }

        if (getRoleLevel((string) $_SESSION['role']) < getRoleLevel('master')) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}

if (!function_exists('iv_fetch_franchisees')) {
    function iv_fetch_franchisees(mysqli $conn, bool $activeOnly = false): array
    {
        $rows = [];
        $sql = "SELECT id, franchisee_code, franchisee_name, status FROM franchisees";
        if ($activeOnly) {
            $sql .= " WHERE status = 'Active'";
        }
        $sql .= " ORDER BY franchisee_name ASC";

        $result = $conn->query($sql);
        while ($result && $row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}
