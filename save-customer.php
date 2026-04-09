<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force MySQLi to throw real errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Correct include path
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

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

    $franchiseeId = iv_current_business_franchisee_id();
    $createdBy = (int) ($_SESSION['user_id'] ?? 0);

    // SQL Insert
    $sql = "INSERT INTO customers
    (customer_name, email, phone, company_name, address, about, dob, status, franchisee_id, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssssssssii",
        $customer_name,
        $email,
        $phone,
        $company_name,
        $address,
        $about,
        $dob,
        $status,
        $franchiseeId,
        $createdBy
    );

    $stmt->execute();

    // Success response
   

    $stmt->close();
    $conn->close();
     header("Location: customers.php");
    exit;
}
?>
