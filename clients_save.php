<?php
include 'db.php';

$name    = $_POST['name'];
$email   = $_POST['email'];
$phone   = $_POST['phone'];
$company = $_POST['company'];
$address = $_POST['address'];
$about   = $_POST['about'];
$dob     = $_POST['dob'];
$status  = $_POST['status'];

// avatar upload
$avatar = null;
if (!empty($_FILES['avatar']['name'])) {
    $avatar = time().'_'.$_FILES['avatar']['name'];
    move_uploaded_file($_FILES['avatar']['tmp_name'], "uploads/".$avatar);
}

$sql = "INSERT INTO clients
(name,email,phone,company,address,about,dob,status,avatar)
VALUES
('$name','$email','$phone','$company','$address','$about','$dob','$status','$avatar')";

mysqli_query($conn, $sql);

header("Location: clients_view.php");
