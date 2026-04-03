<?php
include "db.php";
include "auth.php";

if ($_SESSION['role'] != 'super') {
    die("Access Denied");
}

include "partials/header.php";
?>

<h1>Super Admin Dashboard</h1>
<p>Welcome <?php echo $_SESSION['username']; ?></p>

<?php include "partials/footer.php"; ?>
