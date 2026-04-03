<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/roles.php';

if (!isset($_GET['id'])) {
    die('Project ID missing');
}

$project_id = (int) $_GET['id'];

$projectStmt = $conn->prepare("
    SELECT p.*, e.name AS assigned_employee_name, u.role AS creator_role
    FROM projects p
    LEFT JOIN employees e ON p.assigned_user_id = e.id
    JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();

if ($projectResult->num_rows === 0) {
    die('Project not found');
}

$project = $projectResult->fetch_assoc();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || !canAccessProjectByCreatorRole((int) $_SESSION['user_id'], (string) $_SESSION['role'], (int) $project['created_by'], (string) $project['creator_role'])) {
    http_response_code(403);
    exit('Forbidden');
}

$statusClassMap = [
    'Not Started' => 'secondary',
    'In Progress' => 'primary',
    'On Hold' => 'warning',
    'Finished' => 'success',
    'Declined' => 'danger'
];
$statusClass = $statusClassMap[$project['project_status']] ?? 'secondary';
$progressValue = isset($project['progress']) ? (int) $project['progress'] : 0;
$billingLabelMap = [
    'fixed' => 'Fixed Rate',
    'task_hours' => 'Tasks Hours',
    'project_hours' => 'Project Hours',
    'fixed_rate' => 'Fixed Rate'
];
$billingType = (string)($project['billing_type'] ?? '');
$billingLabel = $billingLabelMap[$billingType] ?? ucwords(str_replace('_', ' ', $billingType));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project View</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
<div class="nxl-content">

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5><?= htmlspecialchars($project['project_name']) ?></h5>
        <small class="text-muted">
            <?= $project['start_date'] ? date('d M Y', strtotime($project['start_date'])) : '-' ?>
            to
            <?= $project['end_date'] ? date('d M Y', strtotime($project['end_date'])) : '-' ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="../project-pdf.php?id=<?= $project_id ?>" class="btn btn-outline-dark">
            <i class="feather-download me-1"></i> Download PDF
        </a>
    </div>
</div>

<div class="card mb-4">
<div class="card-body">
<div class="mb-4">
    <img src="assets/images/Logo_IV.png" style="max-height:60px" alt="Logo">
    <h5 class="mt-2 mb-0">Infinite Vision</h5>
    <small class="text-muted">Project Report</small>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Project Name:</strong> <?= htmlspecialchars($project['project_name']) ?></p>
        <p><strong>Customer:</strong> <?= htmlspecialchars($project['customer_name']) ?></p>
        <p><strong>Project Type:</strong> <?= htmlspecialchars(ucfirst((string)$project['project_type'])) ?></p>
        <p><strong>Billing Type:</strong> <?= htmlspecialchars($billingLabel) ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>Status:</strong> <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($project['project_status']) ?></span></p>
        <p><strong>Project Hours:</strong> <?= (int)$project['project_hours'] ?></p>
        <p><strong>Assigned To:</strong> <?= $project['assigned_employee_name'] ? htmlspecialchars($project['assigned_employee_name']) : 'Unassigned' ?></p>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <p><strong>Start Date:</strong> <?= htmlspecialchars((string)$project['start_date']) ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>End Date:</strong> <?= htmlspecialchars((string)$project['end_date']) ?></p>
    </div>
</div>

<div class="mb-2 d-flex justify-content-between align-items-center">
    <span>Project Progress</span>
    <span><?= $progressValue ?>%</span>
</div>
<div class="progress mb-4" style="height:6px;">
    <div class="progress-bar bg-primary" style="width:<?= $progressValue ?>%"></div>
</div>
</div>
</div>

<div class="card">
<div class="card-body">
    <h6 class="fw-bold">Project Description</h6>
    <p><?= nl2br(htmlspecialchars((string)$project['description'])) ?></p>
</div>
</div>

</div>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>
