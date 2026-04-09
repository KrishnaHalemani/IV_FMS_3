<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';
require_once __DIR__ . '/config/user_management.php';
require_once __DIR__ . '/config/notifications.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: projects-create.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. REQUIRED FIELD VALIDATION
|--------------------------------------------------------------------------
*/
$required = [
    'project_type',
    'project_manage',
    'project_code',
    'project_priority',
    'project_name',
    'description',
    'project_hours',
    'start_date',
    'end_date',
    'billing_type',
    'project_status',
    'assigned_user_id'
];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Missing required field: " . htmlspecialchars($field));
    }
}

/*
|--------------------------------------------------------------------------
| 2. SANITIZE INPUTS
|--------------------------------------------------------------------------
*/
$project_type   = $_POST['project_type'];              // personal | team
$project_manage = $_POST['project_manage'];
$project_code   = trim($_POST['project_code'] ?? '');
$project_priority = trim($_POST['project_priority'] ?? 'medium');
$project_name   = trim($_POST['project_name']);
$customer_id    = ($_POST['customer_id'] ?? '') !== '' ? (int) $_POST['customer_id'] : null;
$franchisee_id  = ($_POST['franchisee_id'] ?? '') !== '' ? (int) $_POST['franchisee_id'] : null;
$related_invoice_id = ($_POST['related_invoice_id'] ?? '') !== '' ? (int) $_POST['related_invoice_id'] : null;
$description    = trim($_POST['description']);
$project_hours  = (int) $_POST['project_hours'];
$start_date     = $_POST['start_date'];
$end_date       = $_POST['end_date'];
$billing_type   = $_POST['billing_type'];
$project_status = $_POST['project_status'];
$estimated_budget = ($_POST['estimated_budget'] ?? '') !== '' ? (float) $_POST['estimated_budget'] : null;
$assigned_user_id = (int) ($_POST['assigned_user_id'] ?? 0);

$employee_ids = $_POST['employee_ids'] ?? [];
$employee_ids = array_map('intval', (array)$employee_ids);
$milestone_titles = $_POST['milestone_title'] ?? [];
$milestone_descriptions = $_POST['milestone_description'] ?? [];
$milestone_due_dates = $_POST['milestone_due_date'] ?? [];

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("User not logged in");
}

$created_by = $_SESSION['user_id']; // fallback for testing
$creator_role = (string) $_SESSION['role'];
$assignableUsers = fetchAssignableUsersIndexed($conn, (int) $created_by, $creator_role);

if (!isset($assignableUsers[$assigned_user_id])) {
    die('Invalid project assignee selected.');
}

$customer_name = 'Internal Project';
if ($customer_id !== null) {
    $customerStmt = $conn->prepare("SELECT customer_name FROM customers WHERE id = ? LIMIT 1");
    $customerStmt->bind_param("i", $customer_id);
    $customerStmt->execute();
    $customerRow = $customerStmt->get_result()->fetch_assoc();
    $customerStmt->close();

    if (!$customerRow) {
        die('Invalid client selected.');
    }

    $customer_name = (string) $customerRow['customer_name'];
}

if ($franchisee_id !== null) {
    $franchiseeStmt = $conn->prepare("SELECT franchisee_name FROM franchisees WHERE id = ? LIMIT 1");
    $franchiseeStmt->bind_param("i", $franchisee_id);
    $franchiseeStmt->execute();
    $franchiseeRow = $franchiseeStmt->get_result()->fetch_assoc();
    $franchiseeStmt->close();

    if (!$franchiseeRow) {
        die('Invalid franchisee selected.');
    }
}

if ($related_invoice_id !== null) {
    $invoiceStmt = $conn->prepare("SELECT to_name FROM invoices WHERE id = ? LIMIT 1");
    $invoiceStmt->bind_param("i", $related_invoice_id);
    $invoiceStmt->execute();
    $invoiceRow = $invoiceStmt->get_result()->fetch_assoc();
    $invoiceStmt->close();

    if (!$invoiceRow) {
        die('Invalid invoice selected.');
    }

    if ($customer_id === null && !empty($invoiceRow['to_name'])) {
        $customer_name = (string) $invoiceRow['to_name'];
    }
}

/*
|--------------------------------------------------------------------------
| 3. EMPLOYEE ASSIGNMENT VALIDATION
|--------------------------------------------------------------------------
*/
$employeeCount = count($employee_ids);

/*
|--------------------------------------------------------------------------
| 4. INSERT PROJECT
|--------------------------------------------------------------------------
*/
$sql = "
    INSERT INTO projects (
        project_name,
        description,
        customer_name,
        customer_id,
        franchisee_id,
        related_invoice_id,
        start_date,
        end_date,
        project_type,
        project_manage,
        project_code,
        project_priority,
        project_hours,
        estimated_budget,
        billing_type,
        project_status,
        assigned_user_id,
        created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssiiissssssidssii",
    $project_name,
    $description,
    $customer_name,
    $customer_id,
    $franchisee_id,
    $related_invoice_id,
    $start_date,
    $end_date,
    $project_type,
    $project_manage,
    $project_code,
    $project_priority,
    $project_hours,
    $estimated_budget,
    $billing_type,
    $project_status,
    $assigned_user_id,
    $created_by
);

if (!$stmt->execute()) {
    die("Project insert failed: " . $stmt->error);
}

$project_id = $stmt->insert_id;
$stmt->close();

$assigneeName = (string) ($assignableUsers[$assigned_user_id]['username'] ?? 'The assigned user');
$creatorName = (string) ($_SESSION['username'] ?? $_SESSION['email'] ?? 'A manager');

/*
|--------------------------------------------------------------------------
| 5. INSERT PROJECT ↔ EMPLOYEE RELATION
|--------------------------------------------------------------------------
*/
$peStmt = $conn->prepare("
    INSERT INTO project_employees (project_id, employee_id)
    VALUES (?, ?)
");

foreach ($employee_ids as $emp_id) {
    $peStmt->bind_param("ii", $project_id, $emp_id);
    $peStmt->execute();
}

$peStmt->close();

$targetStmt = $conn->prepare("
    INSERT INTO project_targets (project_id, title, description, due_date)
    VALUES (?, ?, ?, ?)
");

foreach ($milestone_titles as $index => $title) {
    $milestoneTitle = trim((string) $title);
    $milestoneDescription = trim((string) ($milestone_descriptions[$index] ?? ''));
    $milestoneDueDate = trim((string) ($milestone_due_dates[$index] ?? ''));

    if ($milestoneTitle === '' && $milestoneDescription === '' && $milestoneDueDate === '') {
        continue;
    }

    $dueDateValue = $milestoneDueDate !== '' ? $milestoneDueDate : null;
    $targetStmt->bind_param("isss", $project_id, $milestoneTitle, $milestoneDescription, $dueDateValue);
    $targetStmt->execute();
}

$targetStmt->close();

iv_create_notification(
    $conn,
    $assigned_user_id,
    'project_assigned',
    'New project assigned',
    $creatorName . ' assigned project "' . $project_name . '" to you.',
    'projects.php',
    (int) $created_by
);

/*
|--------------------------------------------------------------------------
| 6. SUCCESS REDIRECT
|--------------------------------------------------------------------------
*/
header("Location: projects.php?created=1");
exit;
?>
