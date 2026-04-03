<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // 🔴 CRITICAL: prevents output issues


require_once __DIR__ . '/../config/db.php';

require_once __DIR__ . '/../tcpdf/tcpdf.php';


if (!isset($_GET['id'])) {
    exit('Invoice ID missing');
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Invoice not found');
}

$invoice = $result->fetch_assoc();

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4');
$pdf->SetCreator('Infinite Vision');
$pdf->SetAuthor('Infinite Vision');
$pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// ✅ SAFE LOGO PATH
$logoPath = __DIR__ . '/assets/images/Logo_IV.png';
$logoHTML = '';

if (file_exists($logoPath)) {
    $logoHTML = '<img src="' . $logoPath . '" height="60"><br><br>';
}

// PDF HTML
$html = '
'.$logoHTML.'
<h2>INFINITE VISION</h2>
<p><strong>A DIVISION OF Awashyambhavi Services Pvt. Ltd. (ASPL)</strong></p>
<p>
B-25, 201, near ICICI Bank, Sector 11<br>
Shanti Nagar, Mira Road East – 401107
</p>

<hr>

<p>
<strong>Invoice #:</strong> '.$invoice['invoice_number'].'<br>
<strong>Date:</strong> '.date('d M Y', strtotime($invoice['issue_date'])).'
</p>

<hr>

<table border="1" cellpadding="6" width="100%">
<tr>
    <th align="left">Invoice From</th>
    <th align="left">Invoice To</th>
</tr>
<tr>
    <td>
        '.$invoice['from_name'].'<br>
        '.$invoice['from_email'].'<br>
        '.$invoice['from_phone'].'<br>
        '.$invoice['from_address'].'
    </td>
    <td>
        '.$invoice['to_name'].'<br>
        '.$invoice['to_email'].'<br>
        '.$invoice['to_phone'].'<br>
        '.$invoice['to_address'].'
    </td>
</tr>
</table>

<br>

<table border="1" cellpadding="6" width="100%">
<tr>
    <td>Sub Total</td>
    <td align="right">'.$invoice['currency'].' '.number_format($invoice['sub_total'], 2).'</td>
</tr>
<tr>
    <td>Tax ('.$invoice['tax_percent'].'%)</td>
    <td align="right">'.$invoice['currency'].' '.number_format($invoice['tax_amount'], 2).'</td>
</tr>
<tr>
    <td>Discount</td>
    <td align="right">'.$invoice['currency'].' '.number_format($invoice['discount'], 2).'</td>
</tr>
<tr>
    <td><strong>Grand Total</strong></td>
    <td align="right"><strong>'.$invoice['currency'].' '.number_format($invoice['grand_total'], 2).'</strong></td>
</tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean(); // 🔴 VERY IMPORTANT

// 🔥 FORCE DOWNLOAD
$pdf->Output(
    'Invoice-'.$invoice['invoice_number'].'.pdf',
    'D'
);
exit;
