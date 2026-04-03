<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_management.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$assignableUsers = fetchAssignableUsers($conn, (int) $_SESSION['user_id'], (string) $_SESSION['role']);
$employees = [];
$res = $conn->query("SELECT id, name FROM employees ORDER BY name");
while ($res && $row = $res->fetch_assoc()) {
    $employees[] = $row;
}

$customers = [];
$customerResult = $conn->query("SELECT id, customer_name, company_name FROM customers ORDER BY customer_name");
while ($customerResult && $row = $customerResult->fetch_assoc()) {
    $customers[] = $row;
}

$invoices = [];
$invoiceResult = $conn->query("SELECT id, invoice_number, to_name, grand_total, currency FROM invoices ORDER BY id DESC LIMIT 100");
while ($invoiceResult && $row = $invoiceResult->fetch_assoc()) {
    $invoices[] = $row;
}

$projectCode = 'PRJ-' . date('Ymd-His');
$projectRole = 'admin';
$projectCreateHeading = 'Create Team Project';
$projectCreateSubheading = 'Set ownership, client linkage, milestones, and delivery dates in one practical form.';
$projectCreateAction = 'projects-store.php';
$projectListUrl = 'projects.php';

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
                    <li class="breadcrumb-item"><a href="adashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Create</li>
                </ul>
            </div>
        </div>

        <?php include __DIR__ . '/../partials/project_create_content.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
