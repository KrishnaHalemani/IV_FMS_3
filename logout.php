<?php
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['email'])) {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/activity_log.php';
    $role = (string) $_SESSION['role'];
    iv_log_activity($conn, 'logout', ucfirst($role) . ' logged out');
}

session_destroy();
header("Location: login.php");
exit;
