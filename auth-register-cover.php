<?php
include "config/db.php";
require_once __DIR__ . '/config/user_management.php';
require_once __DIR__ . '/config/notifications.php';
require_once __DIR__ . '/config/access_control.php';

iv_require_authenticated_session('login.php');

if (getCreatableRoles((string) $_SESSION['role']) === []) {
    http_response_code(403);
    exit('Forbidden');
}

header('Location: employee-create.php?binding_required=1');
exit;
?>
