<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $deleteSql = "DELETE FROM students WHERE id = ?";
    if (!iv_is_master_business_role()) {
        $deleteSql .= " AND franchisee_id = " . (int) iv_current_business_franchisee_id();
    }
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: students.php");
    exit;
}

$result = $conn->query("SELECT * FROM students WHERE " . iv_business_scope_condition('franchisee_id') . " ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <title>IV || Students</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left">
                <h5 class="m-b-10">Students</h5>
            </div>
            <div class="page-header-right ms-auto">
                <a href="students-create.php" class="btn btn-primary btn-sm">
                    <i class="feather-plus me-1"></i> Add Student
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card stretch stretch-full">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="studentList">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Phone</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox"></td>
                                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                                <td><?= htmlspecialchars((string)$row['email']) ?></td>
                                                <td><?= htmlspecialchars((string)$row['course']) ?></td>
                                                <td><?= htmlspecialchars((string)$row['phone']) ?></td>
                                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge <?= $row['status'] === 'Active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' ?>">
                                                        <?= htmlspecialchars($row['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="students-view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-light me-1">
                                                        <i class="feather-eye"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= (int)$row['id'] ?>"
                                                       onclick="return confirm('Delete this student?')"
                                                       class="btn btn-sm btn-danger">
                                                        <i class="feather-trash-2"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
    <?php include 'footer.php'; ?>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/vendors/js/dataTables.min.js"></script>
<script src="assets/vendors/js/dataTables.bs5.min.js"></script>
<script>
$(document).ready(function () {
    $('#studentList').DataTable({
        destroy: true,
        autoWidth: false
    });
});
</script>
</body>
</html>
