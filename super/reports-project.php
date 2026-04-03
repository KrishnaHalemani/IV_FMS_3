<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../partials/project_reports_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'super') {
    header('Location: ../login.php');
    exit;
}

$reportRole = 'super';
$reportBasePath = 'reports-project.php';
$reportProjectsPath = 'projects.php';
$reportCreatePath = 'projects-create.php';
$reportData = iv_fetch_project_report_data($conn, (int) $_SESSION['user_id'], $reportRole, $_GET);

include 'header.php';
?>

<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Reports</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="sdashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Project Reports</li>
                </ul>
            </div>
        </div>

        <?php include __DIR__ . '/../partials/project_reports_content.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
