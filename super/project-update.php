<?php
session_start();
require "../config/db.php";
require "../config/roles.php";

$currentUserId = $_SESSION['user_id'];
$currentRole   = $_SESSION['role'];
$currentLevel  = getRoleLevel($currentRole);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$project_id = (int) $_POST['project_id'];
$progress   = (int) $_POST['progress'];

if ($progress < 0) $progress = 0;
if ($progress > 100) $progress = 100;

// Auto status logic
if ($progress == 100) {
    $status = 'Completed';
} elseif ($progress > 0) {
    $status = 'In Progress';
} else {
    $status = 'Not Started';
}

$stmt = $conn->prepare(
    "UPDATE projects SET progress = ?, project_status = ? WHERE id = ?"
);
$stmt->bind_param("isi", $progress, $status, $project_id);
$stmt->execute();

header("Location: projects-view.php?id=$project_id");
exit;
