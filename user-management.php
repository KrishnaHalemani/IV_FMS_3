<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/user_management.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (getCreatableRoles((string) $_SESSION['role']) === []) {
    http_response_code(403);
    exit('Forbidden');
}

$currentUserId = (int) $_SESSION['user_id'];
$currentRole = (string) $_SESSION['role'];
$visibleUsers = fetchVisibleUsers($conn, $currentUserId, $currentRole);
$homePath = 'index.php';
$registerPath = 'auth-register-cover.php';
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IV || User Management</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="nxl-container">
        <div class="nxl-content">
            <?php include __DIR__ . '/partials/user-management-content.php'; ?>
        </div>
        <?php include 'footer.php'; ?>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/dataTables.min.js"></script>
    <script src="assets/vendors/js/dataTables.bs5.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#userManagementTable').DataTable({
                order: [[4, 'desc']],
                autoWidth: false
            });
        });
    </script>
</body>
</html>
