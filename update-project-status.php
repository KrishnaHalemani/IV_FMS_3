<?php
require "config/db.php";
require "config/roles.php";
require_once __DIR__ . '/config/notifications.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('master')) {
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
    "SELECT project_name, project_status, assigned_user_id, created_by FROM projects WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    http_response_code(404);
    exit('Project not found');
}

$stmt = $conn->prepare("UPDATE projects SET project_status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $project_id);
$stmt->execute();
$stmt->close();

if ((string) $project['project_status'] !== $status) {
    $actorName = (string) ($_SESSION['username'] ?? $_SESSION['email'] ?? 'A team member');
    $recipients = array_filter([
        (int) ($project['assigned_user_id'] ?? 0),
        (int) ($project['created_by'] ?? 0),
    ]);
    $recipients = array_values(array_diff(array_unique($recipients), [$currentUserId]));
    iv_create_notifications_for_users(
        $conn,
        $recipients,
        'project_status',
        'Project status updated',
        $actorName . ' changed "' . (string) $project['project_name'] . '" to ' . $status . '.',
        'projects.php',
        $currentUserId
    );
}

echo "OK";
