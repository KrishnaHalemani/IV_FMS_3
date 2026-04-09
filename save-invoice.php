<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$issue_date = $_POST['issue_date'] ?? date('Y-m-d');
$billing_section = trim($_POST['billing_section'] ?? '');
$party_details = trim($_POST['party_details'] ?? '');
$party_gst_number = trim($_POST['party_gst_number'] ?? '');

$itemDescriptions = $_POST['item_description'] ?? [];
$itemQtys = $_POST['item_qty'] ?? [];
$itemRates = $_POST['item_rate'] ?? [];

if ($billing_section === '' || $party_details === '' || $party_gst_number === '') {
    exit('Required invoice fields are missing.');
}

$lineItems = [];
$sub_total = 0.0;

$maxItems = max(count($itemDescriptions), count($itemQtys), count($itemRates));
for ($i = 0; $i < $maxItems; $i++) {
    $description = trim((string)($itemDescriptions[$i] ?? ''));
    $qty = (float)($itemQtys[$i] ?? 0);
    $rate = (float)($itemRates[$i] ?? 0);

    if ($description === '' || $qty <= 0 || $rate < 0) {
        continue;
    }

    $amount = $qty * $rate;
    $sub_total += $amount;

    $lineItems[] = [
        'description' => $description,
        'qty' => $qty,
        'rate' => $rate,
        'amount' => $amount,
    ];
}

if (count($lineItems) === 0) {
    exit('At least one valid line item is required.');
}

$tax_percent = 18.00;
$tax_amount = ($sub_total * $tax_percent) / 100;
$grand_total = $sub_total + $tax_amount;
$currency = 'INR';
$discount = 0.00;
$franchiseeId = iv_current_business_franchisee_id();
$createdBy = (int) ($_SESSION['user_id'] ?? 0);

$partyLines = preg_split('/\R/', $party_details);
$party_name = trim((string)($partyLines[0] ?? 'Party'));

$conn->begin_transaction();

try {
    $sql = "INSERT INTO invoices (
        issue_date,
        invoice_label,
        invoice_number,
        invoice_product,
        from_name,
        from_email,
        from_phone,
        from_address,
        to_name,
        to_email,
        to_phone,
        to_address,
        invoice_note,
        sub_total,
        tax_percent,
        tax_amount,
        grand_total,
        currency,
        discount,
        franchisee_id,
        created_by
    ) VALUES (?, ?, ?, ?, '', '', '', '', ?, '', ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $invoice_number = $billing_section;
    $invoice_label = $billing_section;
    $invoice_product = $lineItems[0]['description'];

    $stmt->bind_param(
        'sssssssddddsdii',
        $issue_date,
        $invoice_label,
        $invoice_number,
        $invoice_product,
        $party_name,
        $party_gst_number,
        $party_details,
        $sub_total,
        $tax_percent,
        $tax_amount,
        $grand_total,
        $currency,
        $discount,
        $franchiseeId,
        $createdBy
    );

    $stmt->execute();
    $invoiceId = (int)$conn->insert_id;

    $itemsSql = "INSERT INTO invoice_items (invoice_id, description, qty, rate, amount, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)";
    $itemsStmt = $conn->prepare($itemsSql);

    foreach ($lineItems as $index => $item) {
        $sortOrder = $index + 1;
        $itemsStmt->bind_param(
            'isdddi',
            $invoiceId,
            $item['description'],
            $item['qty'],
            $item['rate'],
            $item['amount'],
            $sortOrder
        );
        $itemsStmt->execute();
    }

    $conn->commit();
    header('Location: payment.php');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
