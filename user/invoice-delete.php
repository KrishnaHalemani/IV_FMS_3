<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/access_control.php';
require_once __DIR__ . '/../config/business_scope.php';

iv_require_role_session(['user'], '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isset($_POST['id'])) {
    exit('Invoice ID missing');
}

$invoiceId = (int)$_POST['id'];
if ($invoiceId <= 0) {
    exit('Invalid invoice ID');
}

$deleteSql = 'DELETE FROM invoices WHERE id = ?';
if (!iv_is_master_business_role()) {
    $deleteSql .= ' AND franchisee_id = ' . (int) iv_current_business_franchisee_id();
}
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param('i', $invoiceId);
$stmt->execute();

header('Location: payment.php?deleted=1');
exit;
