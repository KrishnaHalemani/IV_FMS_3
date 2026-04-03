<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    die('Invoice ID missing');
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invoice not found');
}

$invoice = $result->fetch_assoc();
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

<!-- ✅ SIDEBAR MUST COME FIRST -->
<?php include 'sidebar.php'; ?>

<!-- ✅ REQUIRED WRAPPER -->
<main class="nxl-container">
    <div class="nxl-content">

        <!-- PAGE HEADER -->
        <div class="page-header mb-4 d-flex justify-content-between align-items-center">
            <h5>Invoice</h5>
            <a href="invoice-create.php" class="btn btn-primary">
                <i class="feather-plus me-2"></i> Create Invoice
            </a>
        </div>

        <a href="invoice-pdf.php?id=<?= $invoice['id'] ?>" class="btn btn-light-brand">
    <i class="feather-download me-2"></i> Download PDF
</a>


        <!-- INVOICE CARD -->
        <div class="card">
            <div class="card-body">

                <!-- HEADER -->
                <div class="row mb-4 align-items-center">
    <div class="col-md-6 d-flex align-items-start gap-3">

        <!-- LOGO -->
        <div>
            <img 
                src="assets/images/Logo_IV.png" 
                alt="Infinite Vision Logo"
                style="max-height: 70px;"
            >
        </div>

        <!-- COMPANY DETAILS -->
        <div>
            <h3 class="fw-bold mb-1">Infinite Vision</h3>
            <p class="mb-1 fw-semibold">
                A DIVISION OF Awashyambhavi Services Pvt. Ltd. (ASPL)
            </p>
            <p class="mb-0">
                B-25, 201, near ICICI Bank, Sector 11,<br>
                Shanti Nagar, Mira Road East – 401107
            </p>
        </div>

    </div>

    <!-- INVOICE META -->
    <div class="col-md-6 text-end">
        <h4 class="text-primary mb-2">Invoice</h4>
        <p class="mb-1">
            <strong>Invoice:</strong>
            #<?= htmlspecialchars($invoice['invoice_number']) ?>
        </p>
        <p class="mb-0">
            <strong>Issued Date:</strong>
            <?= date('d M Y', strtotime($invoice['issue_date'])) ?>
        </p>
    </div>
</div>


                <hr>

                <!-- FROM / TO -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Invoice From</h6>
                        <p><?= htmlspecialchars($invoice['from_name']) ?></p>
                        <p><?= htmlspecialchars($invoice['from_email']) ?></p>
                        <p><?= htmlspecialchars($invoice['from_phone']) ?></p>
                        <p><?= nl2br(htmlspecialchars($invoice['from_address'])) ?></p>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold">Invoice To</h6>
                        <p><?= htmlspecialchars($invoice['to_name']) ?></p>
                        <p><?= htmlspecialchars($invoice['to_email']) ?></p>
                        <p><?= htmlspecialchars($invoice['to_phone']) ?></p>
                        <p><?= nl2br(htmlspecialchars($invoice['to_address'])) ?></p>
                    </div>
                </div>

                <hr>

                <!-- TOTAL -->
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-bordered">
                            <tr>
                                <th>Sub Total</th>
                                <td><?= $invoice['currency'] ?> <?= number_format($invoice['sub_total'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Tax (<?= $invoice['tax_percent'] ?>%)</th>
                                <td><?= $invoice['currency'] ?> <?= number_format($invoice['tax_amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Discount</th>
                                <td><?= $invoice['currency'] ?> <?= number_format($invoice['discount'], 2) ?></td>
                            </tr>
                            <tr class="fw-bold">
                                <th>Grand Total</th>
                                <td><?= $invoice['currency'] ?> <?= number_format($invoice['grand_total'], 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- NOTE -->
                <?php if (!empty($invoice['invoice_note'])): ?>
                    <hr>
                    <h6 class="fw-bold">Invoice Note</h6>
                    <p><?= nl2br(htmlspecialchars($invoice['invoice_note'])) ?></p>
                <?php endif; ?>

            </div>
        </div>

    </div>
</main>

<!-- ✅ REQUIRED JS (THIS FIXES SIDEBAR TOGGLE) -->
<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
