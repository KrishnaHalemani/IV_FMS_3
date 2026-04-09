<?php
require_once __DIR__ . '/access_control.php';

if (!function_exists('iv_redirect_legacy_app_target')) {
    function iv_redirect_legacy_app_target(string $targetPath, string $loginPath = '../login.php'): void
    {
        iv_require_authenticated_session($loginPath);

        $role = (string) ($_SESSION['role'] ?? '');
        if ($role === 'user') {
            header('Location: ../user/projects.php');
            exit;
        }

        $queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $location = '../' . ltrim($targetPath, '/');
        if ($queryString !== '') {
            $location .= '?' . $queryString;
        }

        header('Location: ' . $location);
        exit;
    }
}
