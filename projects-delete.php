<?php

require "config/db.php";
require "config/roles.php";



/* Accept POST only (since form uses POST) */
if (!isset($_POST['id'])) {
    die("Project ID missing");
}

$projectId = (int) $_POST['id'];

$currentUserId = $_SESSION['user_id'];
$currentRole   = $_SESSION['role'];
$currentLevel  = getRoleLevel($currentRole);

/* Fetch project + creator role */
$stmt = $conn->prepare("
    SELECT p.created_by, u.role
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    die("Project not found");
}

/* Determine creator level */
$creatorLevel = getRoleLevel($project['role']);

/*
    Allow delete only if:
    - current user is higher level than creator
    OR
    - user deleting his own project
*/
if (!($currentLevel > $creatorLevel || $project['created_by'] == $currentUserId)) {
    die("Unauthorized");
}

/* Delete project */
$delete = $conn->prepare("DELETE FROM projects WHERE id = ?");
$delete->bind_param("i", $projectId);
$delete->execute();

/* Redirect back */
header("Location: projects.php?deleted=1");
exit;
