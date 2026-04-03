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

if (!isset($_POST['project_id'], $_POST['status'])) {
    http_response_code(400);
    exit("Invalid request");
}

$projectId = (int) $_POST['project_id'];
$status    = $_POST['status'];

// ✅ Allowed ENUM values (extra safety)
$allowed = [
    'Not Started',
    'In Progress',
    'On Hold',
    'Finished',
    'Declined'
];

if (!in_array($status, $allowed, true)) {
    http_response_code(403);
    exit("Invalid status");
}

/*
|--------------------------------------------------------------------------
| Secure Update With Hierarchy Check
|--------------------------------------------------------------------------
| Rule:
| You can update if:
|   - Your level is HIGHER than creator level
|   - OR you are the creator
*/

$contextStmt = $conn->prepare("SELECT project_name, project_status, assigned_user_id, created_by FROM projects WHERE id = ? LIMIT 1");
$contextStmt->bind_param("i", $projectId);
$contextStmt->execute();
$project = $contextStmt->get_result()->fetch_assoc();
$contextStmt->close();

if (!$project) {
    http_response_code(404);
    exit("Project not found");
}

$stmt = $conn->prepare("
    UPDATE projects p
    JOIN users u ON p.created_by = u.id
    SET p.project_status = ?
    WHERE p.id = ?
    AND (
        ? > (
            CASE u.role
                WHEN 'user' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'super' THEN 3
                WHEN 'master' THEN 4
            END
        )
        OR p.created_by = ?
    )
");

$stmt->bind_param("siii", $status, $projectId, $currentLevel, $currentUserId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(403);
    exit("You are not allowed to update this project");
}

if ((string) $project['project_status'] !== $status) {
    $actorName = (string) ($_SESSION['username'] ?? $_SESSION['email'] ?? 'A team member');
    $recipients = array_filter([
        (int) ($project['assigned_user_id'] ?? 0),
        (int) ($project['created_by'] ?? 0),
    ]);
    $recipients = array_values(array_diff(array_unique($recipients), [(int) $currentUserId]));
    iv_create_notifications_for_users(
        $conn,
        $recipients,
        'project_status',
        'Project status updated',
        $actorName . ' changed "' . (string) $project['project_name'] . '" to ' . $status . '.',
        'projects.php',
        (int) $currentUserId
    );
}

echo "OK";
?>
