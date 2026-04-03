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
    $check = $conn->prepare("SELECT id FROM employees WHERE id = ?");
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

echo "OK";
?>
