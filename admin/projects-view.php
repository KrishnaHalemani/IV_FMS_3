<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    die('Project ID missing');
}

$project_id = (int) $_GET['id'];
$flash = null;
$error = null;
$billingTypeOptions = [
    'fixed' => 'Fixed Rate',
    'task_hours' => 'Tasks Hours',
    'project_hours' => 'Project Hours'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $project_name = trim($_POST['project_name'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
    $project_type = trim($_POST['project_type'] ?? '');
    $billing_type = trim($_POST['billing_type'] ?? '');
    $project_status = trim($_POST['project_status'] ?? '');
    $project_hours = (int) ($_POST['project_hours'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $progress = (int) ($_POST['progress'] ?? 0);
    $assigned_user_id = ($_POST['assigned_user_id'] ?? '') !== '' ? (int) $_POST['assigned_user_id'] : null;

    $allowedType = ['personal', 'team'];
    $allowedStatus = ['Not Started', 'In Progress', 'On Hold', 'Finished', 'Declined'];

    if ($project_name === '' || $customer_name === '') {
        $error = 'Project name and customer are required.';
    } elseif (!in_array(strtolower($project_type), $allowedType, true)) {
        $error = 'Invalid project type.';
    } elseif (!array_key_exists($billing_type, $billingTypeOptions)) {
        $error = 'Invalid billing type.';
    } elseif (!in_array($project_status, $allowedStatus, true)) {
        $error = 'Invalid project status.';
    } elseif ($progress < 0 || $progress > 100) {
        $error = 'Progress must be between 0 and 100.';
    } elseif ($start_date && $end_date && $start_date > $end_date) {
        $error = 'Start date cannot be after end date.';
    } else {
        $sql = "UPDATE projects
                SET project_name = ?, customer_name = ?, project_type = ?, billing_type = ?,
                    project_status = ?, project_hours = ?, start_date = ?, end_date = ?,
                    description = ?, progress = ?, assigned_user_id = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssisssiii",
            $project_name,
            $customer_name,
            $project_type,
            $billing_type,
            $project_status,
            $project_hours,
            $start_date,
            $end_date,
            $description,
            $progress,
            $assigned_user_id,
            $project_id
        );
        $stmt->execute();

        if ($stmt->error) {
            $error = 'Failed to update project.';
        } else {
            header("Location: projects-view.php?id={$project_id}&updated=1");
            exit;
        }
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $flash = 'Project updated successfully.';
}

$projectStmt = $conn->prepare("
    SELECT p.*, e.name AS assigned_employee_name
    FROM projects p
    LEFT JOIN employees e ON p.assigned_user_id = e.id
    WHERE p.id = ?
");
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();

if ($projectResult->num_rows === 0) {
    die('Project not found');
}

$project = $projectResult->fetch_assoc();
$currentBillingType = (string) ($project['billing_type'] ?? '');
if ($currentBillingType === 'fixed_rate') {
    $currentBillingType = 'fixed';
}
if (!array_key_exists($currentBillingType, $billingTypeOptions)) {
    $currentBillingType = 'project_hours';
}

$employees = [];
$employeeResult = $conn->query("SELECT id, name FROM employees ORDER BY name");
if ($employeeResult) {
    while ($row = $employeeResult->fetch_assoc()) {
        $employees[] = $row;
    }
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
        <a href="projects-create.php" class="btn btn-primary">
            <i class="feather-plus me-1"></i> Create Project
        </a>
        <a href="../project-pdf.php?id=<?= $project_id ?>" class="btn btn-outline-dark">
            <i class="feather-download me-1"></i> Download PDF
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="save_project" value="1">

<div class="card mb-4">
<div class="card-body">
<div class="mb-4">
    <img src="assets/images/Logo_IV.png" style="max-height:60px" alt="Logo">
    <h5 class="mt-2 mb-0">Infinite Vision</h5>
    <small class="text-muted">Project Report</small>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <label class="form-label">Project Name</label>
        <input type="text" name="project_name" class="form-control mb-3" value="<?= htmlspecialchars($project['project_name']) ?>" required>

        <label class="form-label">Customer</label>
        <input type="text" name="customer_name" class="form-control mb-3" value="<?= htmlspecialchars($project['customer_name']) ?>" required>

        <label class="form-label">Project Type</label>
        <select name="project_type" class="form-select mb-3" required>
            <option value="personal" <?= strtolower((string)$project['project_type']) === 'personal' ? 'selected' : '' ?>>Personal</option>
            <option value="team" <?= strtolower((string)$project['project_type']) === 'team' ? 'selected' : '' ?>>Team</option>
        </select>

        <label class="form-label">Billing Type</label>
        <select name="billing_type" class="form-select mb-3" required>
            <?php foreach ($billingTypeOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $currentBillingType === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="project_status" class="form-select mb-3" required>
            <?php foreach (['Not Started', 'In Progress', 'On Hold', 'Finished', 'Declined'] as $status): ?>
                <option value="<?= $status ?>" <?= $project['project_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>

        <label class="form-label">Project Hours</label>
        <input type="number" name="project_hours" min="0" class="form-control mb-3" value="<?= (int)$project['project_hours'] ?>">

        <label class="form-label">Assigned To</label>
        <select name="assigned_user_id" class="form-select mb-3">
            <option value="">Unassigned</option>
            <?php foreach ($employees as $employee): ?>
                <option value="<?= (int)$employee['id'] ?>" <?= (int)$project['assigned_user_id'] === (int)$employee['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($employee['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="mb-3">
            <strong>Currently Assigned:</strong>
            <span class="badge bg-light text-dark border">
                <?= $project['assigned_employee_name'] ? htmlspecialchars($project['assigned_employee_name']) : 'Unassigned' ?>
            </span>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string)$project['start_date']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string)$project['end_date']) ?>">
    </div>
</div>

<div class="mb-2 d-flex justify-content-between align-items-center">
    <span>Project Progress</span>
    <span id="progressValue"><?= $progressValue ?>%</span>
</div>
<input type="range" class="form-range mb-3" min="0" max="100" step="1" name="progress" id="progressSlider" value="<?= $progressValue ?>">

<div class="progress mb-4" style="height:6px;">
    <div id="progressBar" class="progress-bar bg-primary" style="width:<?= $progressValue ?>%"></div>
</div>

<div class="mb-3">
    <strong>Status:</strong>
    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($project['project_status']) ?></span>
</div>

<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</div>

<div class="card">
<div class="card-body">
    <h6 class="fw-bold">Project Description</h6>
    <textarea class="form-control" name="description" rows="6"><?= htmlspecialchars((string)$project['description']) ?></textarea>
</div>
</div>

</form>

</div>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>
<script>
const slider = document.getElementById('progressSlider');
const progressBar = document.getElementById('progressBar');
const progressValue = document.getElementById('progressValue');

if (slider && progressBar && progressValue) {
    slider.addEventListener('input', function () {
        const value = Number(this.value) || 0;
        progressBar.style.width = value + '%';
        progressValue.textContent = value + '%';
    });
}
</script>
</body>
</html>
