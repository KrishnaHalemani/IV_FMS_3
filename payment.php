<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

// Fetch invoices
$sql = "SELECT id, invoice_number, to_name, to_email, grand_total, currency, created_at
        FROM invoices
        WHERE " . iv_business_scope_condition('franchisee_id') . "
        ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoices</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
<div class="nxl-content">

<!-- PAGE HEADER -->
<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h5>Payment</h5>
    <a href="invoice-create.php" class="btn btn-primary">
        <i class="feather-plus me-2"></i> Create Invoice
    </a>
</div>

<!-- TABLE -->
<div class="card">
<div class="card-body">

<div class="table-responsive">
<table class="table table-hover" id="invoiceTable">
    <thead>
        <tr>
            <th>Invoice</th>
            <th>Client</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Transaction</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>

    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <!-- Invoice -->
                <td>
                    <strong>#<?= htmlspecialchars($row['invoice_number']) ?></strong>
                </td>

                <!-- Client -->
                <td>
                    <strong><?= htmlspecialchars($row['to_name']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($row['to_email']) ?></small>
                </td>

                <!-- Amount -->
                <td>
                    <?= htmlspecialchars($row['currency']) ?>
                    <?= number_format($row['grand_total'], 2) ?>
                </td>

                <!-- Date -->
                <td>
                    <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                </td>

                <!-- Transaction -->
                <td>
                    #INV<?= $row['id'] ?>
                </td>

                <!-- Status -->
                <td>
                    <span class="badge bg-soft-success text-success">
                        Completed
                    </span>
                </td>

                <!-- Actions -->
                <td class="text-end d-flex justify-content-end gap-1">

                    <!-- VIEW -->
                    <a href="invoice-view.php?id=<?= $row['id'] ?>"
                       class="btn btn-sm btn-light-brand"
                       title="View Invoice">
                        <i class="feather-eye"></i>
                    </a>

                    <!-- PDF -->
                    <a href="invoice-pdf.php?id=<?= $row['id'] ?>"
                       class="btn btn-sm btn-warning"
                       title="Download PDF">
                        <i class="feather-download"></i>
                    </a>

                    <!-- DELETE -->
                    <form method="POST"
                            action="invoice-delete.php"
                            onsubmit="return confirm('Are you sure you want to delete this invoice?');"
                            style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit"
                                class="btn btn-sm btn-danger"
                                title="Delete Invoice">
                            <i class="feather-trash-2"></i>
                        </button>
                    </form>

                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center py-4">
                No invoices found
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

</div>
</div>

</div>
</main>

<!-- JS -->
<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/vendors/js/dataTables.min.js"></script>
<script src="assets/vendors/js/dataTables.bs5.min.js"></script>

<script>
$(document).ready(function () {
    $('#invoiceTable').DataTable({
        order: [[3, 'desc']]
    });
});
</script>

</body>
</html>
