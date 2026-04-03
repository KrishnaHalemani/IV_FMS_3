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

if (!isset($_POST['project_id'], $_POST['field'], $_POST['value'])) {
    http_response_code(400);
    exit('Invalid request');
}

$projectId = (int) $_POST['project_id'];
$field     = $_POST['field'];
$value     = $_POST['value'];

/*
|--------------------------------------------------------------------------
| 1️⃣ Allow ONLY safe columns
|--------------------------------------------------------------------------
*/
$allowedFields = ['start_date', 'end_date'];

if (!in_array($field, $allowedFields, true)) {
    http_response_code(403);
    exit('Invalid field');
}

/*
|--------------------------------------------------------------------------
| 2️⃣ Fetch existing project + creator role (for hierarchy)
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT p.start_date, p.end_date, p.created_by,
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
| 3️⃣ Hierarchy Check
|--------------------------------------------------------------------------
*/
if (
    !(
        $currentLevel > $project['creator_level']
        || $project['created_by'] == $currentUserId
    )
) {
    http_response_code(403);
    exit("You are not allowed to update this project");
}

/*
|--------------------------------------------------------------------------
| 4️⃣ Date Validation
|--------------------------------------------------------------------------
*/
if ($field === 'start_date' && $project['end_date'] && $value > $project['end_date']) {
    http_response_code(422);
    exit('Start date cannot be after end date');
}

if ($field === 'end_date' && $project['start_date'] && $value < $project['start_date']) {
    http_response_code(422);
    exit('End date cannot be before start date');
}

/*
|--------------------------------------------------------------------------
| 5️⃣ Safe Update (column already validated)
|--------------------------------------------------------------------------
*/
$update = $conn->prepare("UPDATE projects SET $field = ? WHERE id = ?");
$update->bind_param("si", $value, $projectId);
$update->execute();

if ($update->affected_rows === 0) {
    http_response_code(403);
    exit("Update failed");
}

echo "OK";
?>
