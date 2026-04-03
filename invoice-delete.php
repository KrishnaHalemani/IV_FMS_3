<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/roles.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('master')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_POST['id'])) {
    exit('Invoice ID missing');
}

$invoiceId = (int)$_POST['id'];
if ($invoiceId <= 0) {
    exit('Invalid invoice ID');
}

$stmt = $conn->prepare('DELETE FROM invoices WHERE id = ?');
$stmt->bind_param('i', $invoiceId);
$stmt->execute();

header('Location: payment.php?deleted=1');
exit;
