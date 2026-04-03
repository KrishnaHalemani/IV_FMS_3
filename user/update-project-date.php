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

if (!isset($_POST['project_id'], $_POST['field'], $_POST['value'])) {
    http_response_code(400);
    exit('Invalid request');
}

$project_id = (int) $_POST['project_id'];
$field = $_POST['field'];
$value = $_POST['value'];
$currentUserId = (int) $_SESSION['user_id'];
$currentRole = (string) $_SESSION['role'];

// allow ONLY these columns (security)
$allowed = ['start_date', 'end_date'];
if (!in_array($field, $allowed)) {
    http_response_code(403);
    exit('Invalid field');
}

/* STEP 1: fetch existing dates and permission info */
$q = $conn->prepare("
    SELECT p.start_date, p.end_date, p.created_by, u.role AS creator_role
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$q->bind_param("i", $project_id);
$q->execute();
$current = $q->get_result()->fetch_assoc();
$q->close();

if (!$current) {
    http_response_code(404);
    exit('Project not found');
}

if (!canAccessProjectByCreatorRole($currentUserId, $currentRole, (int) $current['created_by'], (string) $current['creator_role'])) {
    http_response_code(403);
    exit('Forbidden');
}

/* STEP 2: validate */
if ($field === 'start_date' && $current['end_date'] && $value > $current['end_date']) {
    http_response_code(422);
    exit('Start date cannot be after end date');
}

if ($field === 'end_date' && $value < $current['start_date']) {
    http_response_code(422);
    exit('End date cannot be before start date');
}

/* STEP 3: update only if valid */
$stmt = $conn->prepare("UPDATE projects SET $field = ? WHERE id = ?");
$stmt->bind_param("si", $value, $project_id);
$stmt->execute();

echo "OK";
