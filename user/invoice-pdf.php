<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../tcpdf/tcpdf.php';
require_once __DIR__ . '/../config/access_control.php';
require_once __DIR__ . '/../config/business_scope.php';

iv_require_role_session(['user'], '../login.php');

if (!isset($_GET['id'])) {
    exit('Invoice ID missing');
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
    exit('Invoice not found');
}

$invoice = $result->fetch_assoc();

$itemsStmt = $conn->prepare('SELECT description, qty, rate, amount FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC');
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$itemsRows = '';
$counter = 1;
while ($item = $itemsResult->fetch_assoc()) {
    $desc = htmlspecialchars((string)$item['description'], ENT_QUOTES, 'UTF-8');
    $qty = number_format((float)$item['qty'], 2);
    $rate = number_format((float)$item['rate'], 2);
    $amount = number_format((float)$item['amount'], 2);

    $itemsRows .= '<tr>
<td class="center">' . $counter . '</td>
<td>' . $desc . '</td>
<td class="center">' . $qty . '</td>
<td class="right">' . $rate . '</td>
<td class="right">' . $amount . '</td>
</tr>';

    $counter++;
}

if ($itemsRows === '') {
    $itemsRows = '<tr>
<td class="center">1</td>
<td>' . htmlspecialchars((string)($invoice['invoice_product'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>
<td class="center">-</td>
<td class="right">-</td>
<td class="right">' . number_format((float)$invoice['sub_total'], 2) . '</td>
</tr>';
}

$logoPath = __DIR__ . '/../assets/images/ASPL11.jpg';
$logoHTML = file_exists($logoPath) ? '<img src="' . $logoPath . '" height="60"><br><br>' : '';

$partyDetails = nl2br(htmlspecialchars((string)$invoice['to_address'], ENT_QUOTES, 'UTF-8'));
$partyGst = htmlspecialchars((string)$invoice['to_phone'], ENT_QUOTES, 'UTF-8');
$invoiceNumber = htmlspecialchars((string)$invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
$dateText = date('d-M-y', strtotime((string)$invoice['issue_date']));

$pdf = new TCPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

$html = '
<style>
table { border-collapse: collapse; font-size: 10px; }
td, th { border: 1px solid #000; padding: 4px; }
.center { text-align:center; }
.right { text-align:right; }
.bold { font-weight:bold; }
</style>

' . $logoHTML . '

<table width="100%">
<tr>
<td width="70%"><b>GSTIN: 27ABCCA1355L1ZN</b></td>
<td width="30%" class="right">Original Copy</td>
</tr>

<tr>
<td colspan="2" class="center">
<span style="font-size:14px; font-weight:bold;">TAX INVOICE</span><br>
<span style="font-size:16px; font-weight:bold;">Awashyambavi Services Private Limited</span><br>
Flat No - B/201/25, Akshar Sagar, Shanti Nagar<br>
Mira Road, Thane, Maharashtra, 401107<br>
<span style="font-weight:bold;">Mob. No.: +91 9930142999</span>
</td>
</tr>

<tr>
<td width="70%">
<b>Party Details:</b><br>
' . $partyDetails . '<br>
<b>GST No.:</b> ' . $partyGst . '
</td>
<td width="30%">
<b>Invoice No :</b> ' . $invoiceNumber . '<br>
<b>Dated :</b> ' . $dateText . '
</td>
</tr>
</table>

<br>

<table width="100%">
<tr class="center bold">
<th width="10%">S.N.</th>
<th width="40%">Description</th>
<th width="15%">Qty</th>
<th width="15%">Price</th>
<th width="20%">Amount</th>
</tr>
' . $itemsRows . '
<tr>
<td colspan="4" class="right bold">Add: CGST + SGST / IGST @ ' . number_format((float)$invoice['tax_percent'], 2) . '%</td>
<td class="right">' . number_format((float)$invoice['tax_amount'], 2) . '</td>
</tr>
</table>

<br>

<table width="100%">
<tr>
<td width="60%">
<b>BANK NAME:</b> IDFC FIRST BANK<br>
<b>A/C NAME:</b> AWASHYAMBHAVI SERVICES PRIVATE LIMITED<br>
<b>A/C NO:</b> 59930142995<br>
<b>IFSC:</b> IDFB0040108<br>
<b>BRANCH:</b> GHODBUNDER ROAD-THANE
</td>
<td width="20%" class="right">
<b>Round off</b><br><br>
<b>Grand Total</b>
</td>
<td width="20%" class="right">
0.00<br><br>
<b>' . number_format((float)$invoice['grand_total'], 2) . '</b>
</td>
</tr>
</table>

<br>

<table width="100%">
<tr>
<td width="60%">
<b>Terms & Conditions:</b><br>
1. Services once sold will not be taken back.<br>
2. Interest @ 18% p.a. if not paid on time.<br>
3. Subject to Mumbai jurisdiction.
</td>
<td width="40%" class="center">
For Awashyambavi Services Pvt. Ltd.<br><br>
<br>
Authorized Signatory
</td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
ob_end_clean();
$pdf->Output('Invoice-' . $invoiceNumber . '.pdf', 'D');
exit;
