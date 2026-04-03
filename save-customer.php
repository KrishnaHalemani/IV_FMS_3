<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force MySQLi to throw real errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Correct include path
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect POST data
    $customer_name = trim($_POST['customer_name'] ?? '');
    $email         = $_POST['email'] ?? null;
    $phone         = $_POST['phone'] ?? null;
    $company_name  = $_POST['company_name'] ?? null;
    $address       = $_POST['address'] ?? null;
    $about         = $_POST['about'] ?? null;
    $dob           = $_POST['dob'] ?? null;
    $status        = $_POST['status'] ?? 'Active';

    // Validation
    if ($customer_name === '') {
        die('Customer name is required');
    }

    // SQL Insert
    $sql = "INSERT INTO customers
    (customer_name, email, phone, company_name, address, about, dob, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssssssss",
        $customer_name,
        $email,
        $phone,
        $company_name,
        $address,
        $about,
        $dob,
        $status
    );

    $stmt->execute();

    // Success response
   

    $stmt->close();
    $conn->close();
     header("Location: customers.php");
    exit;
}
?>
