<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/partials/projects_page_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$projectsRole = (string) $_SESSION['role'];
$projectsData = iv_fetch_project_workspace($conn, (int) $_SESSION['user_id'], $projectsRole, $_GET);
$projectsPageHeading = 'Project Portfolio';
$projectsPageSubheading = 'Track ownership, delivery pressure, and linked business records from one workspace.';
$projectsCreateUrl = in_array($projectsRole, ['master', 'super', 'admin'], true) ? 'projects-create.php' : '';
$projectsReportUrl = 'reports-project.php';
$projectsIndexUrl = 'projects.php';
$projectsViewUrlPrefix = 'projects-view.php?id=';
$projectsDeleteUrl = 'projects-delete.php';

include 'header.php';
?>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Projects</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Portfolio</li>
                </ul>
            </div>
        </div>

        <?php include __DIR__ . '/partials/projects_list_content.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
