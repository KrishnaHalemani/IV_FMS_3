<?php

require "../config/db.php";
require "../config/roles.php";

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

echo "OK";
?>
