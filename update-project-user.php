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

if (!isset($_POST['project_id'], $_POST['user_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$project_id = (int) $_POST['project_id'];
$user_id = $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;

$stmt = $conn->prepare(
    "UPDATE projects SET assigned_user_id = ? WHERE id = ?"
);
$stmt->bind_param("ii", $user_id, $project_id);
$stmt->execute();

echo "OK";
