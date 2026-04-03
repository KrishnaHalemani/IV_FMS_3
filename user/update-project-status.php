<?php
require "../config/db.php";
require "../config/roles.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('user')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_POST['project_id'], $_POST['status'])) {
    http_response_code(400);
    exit('Invalid request');
}

$project_id = (int) $_POST['project_id'];
$status = $_POST['status'];
$currentUserId = (int) $_SESSION['user_id'];
$currentRole = (string) $_SESSION['role'];

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

$projectStmt = $conn->prepare("
    SELECT p.created_by, u.role AS creator_role
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$project = $projectStmt->get_result()->fetch_assoc();
$projectStmt->close();

if (!$project) {
    http_response_code(404);
    exit('Project not found');
}

if (!canAccessProjectByCreatorRole($currentUserId, $currentRole, (int) $project['created_by'], (string) $project['creator_role'])) {
    http_response_code(403);
    exit('Forbidden');
}

$stmt = $conn->prepare(
    "UPDATE projects SET project_status = ? WHERE id = ?"
);
$stmt->bind_param("si", $status, $project_id);
$stmt->execute();

echo "OK";
