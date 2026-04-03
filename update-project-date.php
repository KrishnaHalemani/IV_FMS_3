<?php
require "config/db.php";
require "config/roles.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('master')) {
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

// allow ONLY these columns (security)
$allowed = ['start_date', 'end_date'];
if (!in_array($field, $allowed)) {
    http_response_code(403);
    exit('Invalid field');
}

/* STEP 1: fetch existing dates */
$q = $conn->prepare("SELECT start_date, end_date FROM projects WHERE id = ?");
$q->bind_param("i", $project_id);
$q->execute();
$current = $q->get_result()->fetch_assoc();

if (!$current) {
    http_response_code(404);
    exit('Project not found');
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
