<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';
require_once __DIR__ . '/config/user_management.php';

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
    'project_name',
    'customer_name',
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
$project_name   = trim($_POST['project_name']);
$customer_name  = trim($_POST['customer_name']);
$description    = trim($_POST['description']);
$project_hours  = (int) $_POST['project_hours'];
$start_date     = $_POST['start_date'];
$end_date       = $_POST['end_date'];
$billing_type   = $_POST['billing_type'];
$project_status = $_POST['project_status'];
$assigned_user_id = (int) ($_POST['assigned_user_id'] ?? 0);

$employee_ids = $_POST['employee_ids'] ?? [];
$employee_ids = array_map('intval', (array)$employee_ids);

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("User not logged in");
}

$created_by = $_SESSION['user_id']; // fallback for testing
$creator_role = (string) $_SESSION['role'];
$assignableUsers = fetchAssignableUsersIndexed($conn, (int) $created_by, $creator_role);

if (!isset($assignableUsers[$assigned_user_id])) {
    die('Invalid project assignee selected.');
}

/*
|--------------------------------------------------------------------------
| 3. EMPLOYEE ASSIGNMENT VALIDATION
|--------------------------------------------------------------------------
*/
$employeeCount = count($employee_ids);

if ($project_type === 'personal' && $employeeCount !== 1) {
    die('Personal project must have exactly ONE assigned employee.');
}

if ($project_type === 'team' && $employeeCount < 1) {
    die('Team project must have at least ONE assigned employee.');
}

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
        start_date,
        end_date,
        project_type,
        project_manage,
        project_hours,
        billing_type,
        project_status,
        assigned_user_id,
        created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssissii",
    $project_name,
    $description,
    $customer_name,
    $start_date,
    $end_date,
    $project_type,
    $project_manage,
    $project_hours,
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

/*
|--------------------------------------------------------------------------
| 6. SUCCESS REDIRECT
|--------------------------------------------------------------------------
*/
header("Location: projects.php?created=1");
exit;
?>
