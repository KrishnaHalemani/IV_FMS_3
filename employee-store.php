<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employees.php");
    exit;
}

$name   = trim($_POST['name']);
$email  = trim($_POST['email']);
$phone  = trim($_POST['phone']);
$status = $_POST['status'];

$stmt = $conn->prepare("
    INSERT INTO employees (name, email, phone, status, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

$stmt->bind_param("ssss", $name, $email, $phone, $status);
$stmt->execute();

header("Location: employees.php");
exit;
