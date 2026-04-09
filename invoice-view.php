<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_authenticated_session('login.php');

if (!isset($_GET['id'])) {
    die('Invoice ID missing');
}

$id = (int)$_GET['id'];

$invoiceSql = 'SELECT * FROM invoices WHERE id = ?';
if (!iv_is_master_business_role()) {
    $invoiceSql .= ' AND franchisee_id = ' . (int) iv_current_business_franchisee_id();
}
$stmt = $conn->prepare($invoiceSql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invoice not found');
}

$invoice = $result->fetch_assoc();

$itemsStmt = $conn->prepare('SELECT description, qty, rate, amount FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC');
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice View</title>
<link rel="shortcut icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
<link rel="stylesheet" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="nxl-container">
<div class="nxl-content">

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h5>Invoice</h5>
    <a href="invoice-pdf.php?id=<?= (int)$invoice['id'] ?>" class="btn btn-light-brand">
        <i class="feather-download me-2"></i> Download PDF
    </a>
</div>

<div class="card">
<div class="card-body">
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <h6 class="fw-bold">Party Details</h6>
        <p class="mb-1"><?= nl2br(htmlspecialchars((string)$invoice['to_address'])) ?></p>
        <p class="mb-0"><strong>GST No:</strong> <?= htmlspecialchars((string)$invoice['to_phone']) ?></p>
    </div>
    <div class="col-md-6 text-md-end">
        <p class="mb-1"><strong>Billing Section:</strong> <?= htmlspecialchars((string)$invoice['invoice_label']) ?></p>
        <p class="mb-0"><strong>Date:</strong> <?= date('d M Y', strtotime((string)$invoice['issue_date'])) ?></p>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>S.N.</th>
            <th>Description</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Rate</th>
            <th class="text-end">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php $sn = 1; while ($item = $items->fetch_assoc()): ?>
        <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars((string)$item['description']) ?></td>
            <td class="text-center"><?= number_format((float)$item['qty'], 2) ?></td>
            <td class="text-end"><?= number_format((float)$item['rate'], 2) ?></td>
            <td class="text-end"><?= number_format((float)$item['amount'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="4" class="text-end fw-bold">Tax (<?= number_format((float)$invoice['tax_percent'], 2) ?>%)</td>
            <td class="text-end"><?= number_format((float)$invoice['tax_amount'], 2) ?></td>
        </tr>
        <tr class="fw-bold">
            <td colspan="4" class="text-end">Grand Total</td>
            <td class="text-end"><?= htmlspecialchars((string)$invoice['currency']) ?> <?= number_format((float)$invoice['grand_total'], 2) ?></td>
        </tr>
    </tbody>
</table>
</div>
</div>
</div>

</div>
</main>
<script src="assets/vendors/js/vendors.min.js"></script>
</body>
</html>
