<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        discount
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $issue_date      = $_POST['issue_date'] ?? null;
    $invoice_label   = $_POST['invoice_label'] ?? '';
    $invoice_number  = $_POST['invoice_number'] ?? '';
    $invoice_product = $_POST['invoice_product'] ?? '';

    $from_name    = $_POST['from_name'] ?? '';
    $from_email   = $_POST['from_email'] ?? '';
    $from_phone   = $_POST['from_phone'] ?? '';
    $from_address = $_POST['from_address'] ?? '';

    $to_name    = $_POST['to_name'] ?? '';
    $to_email   = $_POST['to_email'] ?? '';
    $to_phone   = $_POST['to_phone'] ?? '';
    $to_address = $_POST['to_address'] ?? '';

    $invoice_note = $_POST['invoice_note'] ?? '';

    $sub_total   = (float)($_POST['sub_total'] ?? 0);
    $tax_percent = (float)($_POST['tax_percent'] ?? 0);
    $tax_amount  = (float)($_POST['tax_amount'] ?? 0);
    $grand_total = (float)($_POST['grand_total'] ?? 0);

    $currency = $_POST['currency'] ?? 'INR';
    $discount = (float)($_POST['discount'] ?? 0);

    // ✅ EXACT MATCH — 19 TYPES, 19 VARIABLES
    $stmt->bind_param(
        "sssssssssssssddddsd",
        $issue_date,
        $invoice_label,
        $invoice_number,
        $invoice_product,

        $from_name,
        $from_email,
        $from_phone,
        $from_address,

        $to_name,
        $to_email,
        $to_phone,
        $to_address,

        $invoice_note,

        $sub_total,
        $tax_percent,
        $tax_amount,
        $grand_total,

        $currency,
        $discount
    );

    $stmt->execute();

   header("Location: payment.php");
    exit;
}
