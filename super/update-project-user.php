<?php

require "../config/db.php";
require "../config/roles.php";
require_once __DIR__ . '/../config/notifications.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$currentUserId = $_SESSION['user_id'];
$currentRole   = $_SESSION['role'];
$currentLevel  = getRoleLevel($currentRole);

if (!isset($_POST['project_id'], $_POST['user_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$projectId = (int) $_POST['project_id'];
$userId    = $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;

/*
|--------------------------------------------------------------------------
| 1️⃣ Fetch project + creator level (for hierarchy check)
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT p.created_by,
           p.project_name,
           p.assigned_user_id,
           CASE u.role
                WHEN 'user' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'super' THEN 3
                WHEN 'master' THEN 4
           END as creator_level
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    http_response_code(404);
    exit("Project not found");
}

/*
|--------------------------------------------------------------------------
| 2️⃣ Hierarchy Check
|--------------------------------------------------------------------------
*/
if (
    !(
        $currentLevel > $project['creator_level']
        || $project['created_by'] == $currentUserId
    )
) {
    http_response_code(403);
    exit("You are not allowed to modify this project");
}

/*
|--------------------------------------------------------------------------
| 3️⃣ Optional: Validate employee exists (if not null)
|--------------------------------------------------------------------------
*/
if ($userId !== null) {
    $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();

    if (!$exists) {
        http_response_code(404);
        exit("Employee not found");
    }
}

/*
|--------------------------------------------------------------------------
| 4️⃣ Update Assignment
|--------------------------------------------------------------------------
*/
$update = $conn->prepare("
    UPDATE projects 
    SET assigned_user_id = ? 
    WHERE id = ?
");
$update->bind_param("ii", $userId, $projectId);
$update->execute();

if ($update->affected_rows === 0) {
    http_response_code(403);
    exit("Update failed");
}

$actorName = (string) ($_SESSION['username'] ?? $_SESSION['email'] ?? 'A manager');
$oldUserId = (int) ($project['assigned_user_id'] ?? 0);

if ($userId !== null && $userId !== $oldUserId) {
    iv_create_notification(
        $conn,
        $userId,
        'project_assigned',
        'Project assignment updated',
        $actorName . ' assigned project "' . (string) $project['project_name'] . '" to you.',
        'projects.php',
        (int) $currentUserId
    );
}

if ($oldUserId > 0 && $oldUserId !== $userId && $oldUserId !== (int) $currentUserId) {
    iv_create_notification(
        $conn,
        $oldUserId,
        'project_unassigned',
        'Project assignment changed',
        'You are no longer assigned to project "' . (string) $project['project_name'] . '".',
        'projects.php',
        (int) $currentUserId
    );
}

echo "OK";
?>
