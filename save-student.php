<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $course = $_POST['course'] ?? null;
    $address = $_POST['address'] ?? null;
    $about = $_POST['about'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $status = $_POST['status'] ?? 'Active';

    if ($student_name === '') {
        die('Student name is required');
    }

    $franchiseeId = iv_current_business_franchisee_id();
    $createdBy = (int) ($_SESSION['user_id'] ?? 0);

    $sql = "INSERT INTO students
    (student_name, email, phone, course, address, about, dob, status, franchisee_id, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssii",
        $student_name,
        $email,
        $phone,
        $course,
        $address,
        $about,
        $dob,
        $status,
        $franchiseeId,
        $createdBy
    );
    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: students.php");
    exit;
}
?>
