<?php
include "config/db.php";

if (!isset($_SESSION['role'])) {
    header("Location: auth/login.php");
    exit;
}

switch ($_SESSION['role']) {
    case 'master':
        header("Location: index.php");
        break;
    case 'super':
        header("Location: super/sdashboard.php");
        break;
    case 'admin':
        header("Location: admin/adashboard.php");
        break;
    case 'user':
        header("Location: user/projects.php");
        break;
}
exit;
