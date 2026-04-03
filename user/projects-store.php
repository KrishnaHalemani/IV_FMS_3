<?php
require '../config/db.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}

http_response_code(403);
exit('Project creation is only available for admin, superadmin, and master accounts.');
?>
