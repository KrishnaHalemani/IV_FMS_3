<?php
require_once __DIR__ . '/../config/db.php';

/* DELETE CUSTOMER */
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: customers.php");
    exit;
}

/* FETCH CUSTOMERS */
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <title>IV || Customers</title>

    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>

<body>

<!-- ✅ SIDEBAR (UNCHANGED) -->
<?php include 'sidebar.php'; ?>

<!-- ✅ REQUIRED BY THEME -->
<main class="nxl-container">
    <div class="nxl-content">

        <!-- PAGE HEADER (KEEP YOUR EXISTING ONE IF ANY) -->
        <div class="page-header">
            <div class="page-header-left">
                <h5 class="m-b-10">Customers</h5>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card stretch stretch-full">
                        <div class="card-body p-0">
                            <div class="table-responsive">

                                <table class="table table-hover" id="customerList">

                                    <!-- THEAD (8 columns) -->
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Group</th>
                                        <th>Phone</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Delete</th>
                                    </tr>
                                    </thead>

                                    <!-- TBODY (ALWAYS 8 TDs) -->
                                    <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox"></td>
                                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td>-</td>
                                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge <?= $row['status'] === 'Active'
                                                        ? 'bg-soft-success text-success'
                                                        : 'bg-soft-danger text-danger' ?>">
                                                        <?= $row['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="?delete_id=<?= $row['id'] ?>"
                                                       onclick="return confirm('Delete this customer?')"
                                                       class="btn btn-sm btn-danger">
                                                        <i class="feather-trash-2"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td></td>
                                            <td>No customers found</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>

                                </table>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- ✅ FOOTER (UNCHANGED) -->
    <?php include 'footer.php'; ?>
</main>

<!-- JS (ORDER MATTERS FOR THEME) -->
<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/vendors/js/dataTables.min.js"></script>
<script src="assets/vendors/js/dataTables.bs5.min.js"></script>

<script>
$(document).ready(function () {
    $('#customerList').DataTable({
        destroy: true,
        autoWidth: false
    });
});
</script>

</body>
</html>
