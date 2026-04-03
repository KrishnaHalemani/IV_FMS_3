<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$conn = mysqli_connect("localhost", "root", "", "fms");

if (!$conn) {
    die("Database connection failed");
}
mysqli_set_charset($conn, "utf8mb4");

