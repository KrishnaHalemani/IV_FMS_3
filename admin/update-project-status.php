<?php
require "../config/db.php";
require "../config/roles.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('admin')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_POST['project_id'], $_POST['status'])) {
    http_response_code(400);
    exit('Invalid request');
}

$project_id = (int) $_POST['project_id'];
$status = $_POST['status'];

// allowed ENUM values (extra safety)
$allowed = [
    'Not Started',
    'In Progress',
    'On Hold',
    'Finished',
    'Declined'
];

if (!in_array($status, $allowed, true)) {
    http_response_code(403);
    exit('Invalid status');
}

$stmt = $conn->prepare(
    "UPDATE projects SET project_status = ? WHERE id = ?"
);
$stmt->bind_param("si", $status, $project_id);
$stmt->execute();

echo "OK";
