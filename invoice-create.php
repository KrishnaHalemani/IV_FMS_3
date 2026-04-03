<?php
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IV || Invoice Create</title>

    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="nxl-container">
<div class="nxl-content">
<form method="POST" action="save-invoice.php" id="invoiceForm">

<div class="page-header d-flex justify-content-between align-items-center">
    <h5>Invoice</h5>
    <button type="submit" class="btn btn-primary">
        <i class="feather-save me-2"></i> Save Invoice
    </button>
</div>

<div class="main-content">
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="issue_date" class="form-control" value="<?= htmlspecialchars($today) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Billing Section</label>
                        <input type="text" name="billing_section" class="form-control" placeholder="e.g. INV-2026-001" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Party Details</label>
                        <textarea name="party_details" rows="4" class="form-control" placeholder="Name and billing address" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Party GST Number</label>
                        <input type="text" name="party_gst_number" class="form-control" placeholder="GSTIN" required>
                    </div>
                </div>

                <hr>

                <h6>Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th width="120">Qty</th>
                                <th width="150">Rate</th>
                                <th width="150">Amount</th>
                                <th width="60"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-sm btn-primary mt-3" onclick="addRow()">
                    + Add Item
                </button>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-2">GST rate is fixed at <strong>18%</strong>.</p>

                <label class="form-label">Sub Total</label>
                <input type="number" step="0.01" id="sub_total" name="sub_total" class="form-control mb-2" readonly>

                <label class="form-label">Tax (18%)</label>
                <input type="number" step="0.01" id="tax_amount" name="tax_amount" class="form-control mb-2" readonly>

                <label class="form-label">Grand Total</label>
                <input type="number" step="0.01" id="grand_total" name="grand_total" class="form-control fw-bold" readonly>

                <input type="hidden" name="tax_percent" value="18">
                <input type="hidden" name="currency" value="INR">
                <input type="hidden" name="discount" value="0">
            </div>
        </div>
    </div>
</div>
</div>

</form>
</div>
<?php include 'footer.php'; ?>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script>
const GST_PERCENT = 18;

function addRow() {
    const row = `
    <tr>
        <td><input name="item_description[]" class="form-control" required></td>
        <td><input name="item_qty[]" type="number" min="0" step="0.01" class="form-control qty" required></td>
        <td><input name="item_rate[]" type="number" min="0" step="0.01" class="form-control rate" required></td>
        <td><input name="item_amount[]" class="form-control amount" readonly></td>
        <td><button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm">X</button></td>
    </tr>`;

    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', row);
}

function removeRow(button) {
    button.closest('tr').remove();
    updateTotals();
}

function updateTotals() {
    let subTotal = 0;

    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const rate = parseFloat(row.querySelector('.rate').value) || 0;
        const amount = qty * rate;

        row.querySelector('.amount').value = amount.toFixed(2);
        subTotal += amount;
    });

    const taxAmount = (subTotal * GST_PERCENT) / 100;
    const grandTotal = subTotal + taxAmount;

    document.getElementById('sub_total').value = subTotal.toFixed(2);
    document.getElementById('tax_amount').value = taxAmount.toFixed(2);
    document.getElementById('grand_total').value = grandTotal.toFixed(2);
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('qty') || e.target.classList.contains('rate')) {
        updateTotals();
    }
});

addRow();
updateTotals();
</script>
</body>
</html>


