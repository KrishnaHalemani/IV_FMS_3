<?php
require_once __DIR__ . '/roles.php';

if (!function_exists('iv_require_authenticated_session')) {
    function iv_require_authenticated_session(string $loginPath = 'login.php'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            header('Location: ' . $loginPath);
            exit;
        }
    }
}

if (!function_exists('iv_require_role_session')) {
    function iv_require_role_session(array $allowedRoles, string $loginPath = 'login.php'): void
    {
        iv_require_authenticated_session($loginPath);

        $currentRole = (string) ($_SESSION['role'] ?? '');
        if (!in_array($currentRole, $allowedRoles, true)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
?>
