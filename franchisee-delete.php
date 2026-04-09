<?php
require_once __DIR__ . '/config/franchisees.php';

iv_require_master_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: franchisees.php');
    exit;
}

$franchiseeId = (int) ($_POST['id'] ?? 0);
if ($franchiseeId > 0) {
    $stmt = $conn->prepare("DELETE FROM franchisees WHERE id = ?");
    $stmt->bind_param("i", $franchiseeId);
    $stmt->execute();
    $stmt->close();
}

header('Location: franchisees.php?deleted=1');
exit;
