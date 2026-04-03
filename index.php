<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/partials/dashboard_helpers.php';

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

$dashboardRole = 'master';
$dashboardMetrics = iv_fetch_dashboard_metrics($conn, (int) $_SESSION['user_id'], $dashboardRole);

include 'header.php';
?>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Dashboard</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Master</li>
                </ul>
            </div>
        </div>

        <?php include __DIR__ . '/partials/dashboard_content.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
