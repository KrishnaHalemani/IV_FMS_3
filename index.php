
<?php
include 'header.php';
require_once __DIR__ . '/config/db.php';

/* =========================
   DEFAULT SAFE VALUES
========================= */
$totalInvoices = 0;
$awaitingInvoices = 0;
$invoicePercent = 0;
$totalCustomers = 0;
$totalCollected = 0;

/* =========================
   INVOICES COUNTS
========================= */

// Total invoices
$resInvoices = $conn->query("SELECT COUNT(*) AS total FROM invoices");
if ($resInvoices) {
    $totalInvoices = (int) $resInvoices->fetch_assoc()['total'];
}

// Awaiting payment logic (no status column assumption)
// All invoices having grand_total > 0
$resAwaiting = $conn->query("
    SELECT COUNT(*) AS awaiting 
    FROM invoices 
    WHERE grand_total > 0
");
if ($resAwaiting) {
    $awaitingInvoices = (int) $resAwaiting->fetch_assoc()['awaiting'];
}

// Percentage
if ($totalInvoices > 0) {
    $invoicePercent = round(($awaitingInvoices / $totalInvoices) * 100);
}

/* =========================
   TOTAL AMOUNT COLLECTED
========================= */

$resTotal = $conn->query("SELECT SUM(grand_total) AS total FROM invoices");
if ($resTotal) {
    $totalCollected = $resTotal->fetch_assoc()['total'] ?? 0;
}

/* =========================
   CUSTOMERS COUNT
========================= */

$resCustomers = $conn->query("SELECT COUNT(*) AS total FROM customers");
if ($resCustomers) {
    $totalCustomers = (int) $resCustomers->fetch_assoc()['total'];
}
?>

<body>

<?php include "sidebar.php"; ?>

<main class="nxl-container">
    
<div class="nxl-content">

<!-- MAIN CONTENT -->
<div class="main-content">
<div class="row">

<!-- ================= INVOICES AWAITING PAYMENT ================= -->
<div class="col-xxl-3 col-md-6">
    <div class="card stretch stretch-full">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div class="d-flex gap-4 align-items-center">
                    <div class="avatar-text avatar-lg bg-gray-200">
                        <i class="feather-dollar-sign"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">
                            <?= $awaitingInvoices ?> / <?= $totalInvoices ?>
                        </div>
                        <h3 class="fs-13 fw-semibold text-truncate-1-line">
                            Invoices Awaiting Payment
                        </h3>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fs-12 fw-medium text-muted">Awaiting</span>
                    <span class="fs-12 text-dark"><?= $invoicePercent ?>%</span>
                </div>
                <div class="progress mt-2 ht-3">
                    <div class="progress-bar bg-primary"
                        role="progressbar"
                        style="width: <?= $invoicePercent ?>%">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= CLIENTS CREATED ================= -->
<div class="col-xxl-3 col-md-6">
    <div class="card stretch stretch-full">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div class="d-flex gap-4 align-items-center">
                    <div class="avatar-text avatar-lg bg-gray-200">
                        <i class="feather-users"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">
                            <?= $totalCustomers ?>
                        </div>
                        <h3 class="fs-13 fw-semibold text-truncate-1-line">
                            Clients Created
                        </h3>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <div class="progress mt-2 ht-3">
                    <div class="progress-bar bg-success"
                        role="progressbar"
                        style="width: 100%">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= TOTAL AMOUNT COLLECTED ================= -->
<div class="col-xxl-3 col-md-6">
    <div class="card stretch stretch-full">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div class="d-flex gap-4 align-items-center">
                    <div class="avatar-text avatar-lg bg-gray-200">
                        <i class="feather-bar-chart-2"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">
                            ₹<?= number_format($totalCollected, 2) ?>
                        </div>
                        <h3 class="fs-13 fw-semibold text-truncate-1-line">
                            Total Amount Collected
                        </h3>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <span class="fs-12 text-muted">From all invoices</span>
            </div>
        </div>
    </div>
</div>

<!-- 🔒 REST OF YOUR DASHBOARD REMAINS UNCHANGED 🔒 -->

</div>
</div>

</div>
</main>


<?php include 'footer.php'; ?>
