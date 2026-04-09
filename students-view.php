<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/access_control.php';
require_once __DIR__ . '/config/business_scope.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    die('Invalid student ID');
}

$studentSql = "SELECT * FROM students WHERE id = ?";
if (!iv_is_master_business_role()) {
    $studentSql .= " AND franchisee_id = " . (int) iv_current_business_franchisee_id();
}
$studentSql .= " LIMIT 1";
$stmt = $conn->prepare($studentSql);
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die('Student not found');
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <title>IV || Student View</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left">
                <h5 class="m-b-10">Student View</h5>
            </div>
            <div class="page-header-right ms-auto">
                <a href="students.php" class="btn btn-light btn-sm">Back to Students</a>
            </div>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4"><?= htmlspecialchars($student['student_name']) ?></h4>
                            <div class="row g-3">
                                <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars((string) $student['email']) ?></div>
                                <div class="col-md-6"><strong>Phone:</strong> <?= htmlspecialchars((string) $student['phone']) ?></div>
                                <div class="col-md-6"><strong>Course:</strong> <?= htmlspecialchars((string) $student['course']) ?></div>
                                <div class="col-md-6"><strong>Date of Birth:</strong> <?= htmlspecialchars((string) $student['dob']) ?></div>
                                <div class="col-md-6"><strong>Status:</strong> <?= htmlspecialchars((string) $student['status']) ?></div>
                                <div class="col-md-6"><strong>Created:</strong> <?= htmlspecialchars((string) $student['created_at']) ?></div>
                                <div class="col-12"><strong>Address:</strong> <?= nl2br(htmlspecialchars((string) $student['address'])) ?></div>
                                <div class="col-12"><strong>About:</strong> <?= nl2br(htmlspecialchars((string) $student['about'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</main>
</body>
</html>
