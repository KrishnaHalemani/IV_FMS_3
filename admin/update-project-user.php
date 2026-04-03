<?php
require "../config/db.php";
require "../config/roles.php";
require_once __DIR__ . '/../config/notifications.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('admin')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_POST['project_id'], $_POST['user_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$project_id = (int) $_POST['project_id'];
$user_id = $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;
$currentUserId = (int) $_SESSION['user_id'];

$contextStmt = $conn->prepare("SELECT project_name, assigned_user_id, created_by FROM projects WHERE id = ? LIMIT 1");
$contextStmt->bind_param("i", $project_id);
$contextStmt->execute();
$project = $contextStmt->get_result()->fetch_assoc();
$contextStmt->close();

if (!$project) {
    http_response_code(404);
    exit('Project not found');
}

$stmt = $conn->prepare("UPDATE projects SET assigned_user_id = ? WHERE id = ?");
$stmt->bind_param("ii", $user_id, $project_id);
$stmt->execute();
$stmt->close();

$actorName = (string) ($_SESSION['username'] ?? $_SESSION['email'] ?? 'A manager');
$oldUserId = (int) ($project['assigned_user_id'] ?? 0);

if ($user_id !== null && $user_id !== $oldUserId) {
    iv_create_notification(
        $conn,
        $user_id,
        'project_assigned',
        'Project assignment updated',
        $actorName . ' assigned project "' . (string) $project['project_name'] . '" to you.',
        'projects.php',
        $currentUserId
    );
}

if ($oldUserId > 0 && $oldUserId !== $user_id && $oldUserId !== $currentUserId) {
    iv_create_notification(
        $conn,
        $oldUserId,
        'project_unassigned',
        'Project assignment changed',
        'You are no longer assigned to project "' . (string) $project['project_name'] . '".',
        'projects.php',
        $currentUserId
    );
}

echo "OK";
