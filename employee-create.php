<?php
require_once __DIR__ . '/config/db.php';

$error = "";
$success = "";

$name = $email = $phone = $role = $status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $phone  = trim($_POST['phone']);
    $role   = trim($_POST['role']);
    $status = $_POST['status'];

    if ($name === '' || $email === '' || $role === '') {
        $error = "Please fill all required fields.";
    } else {

        // ✅ CHECK DUPLICATE EMAIL
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Employee with this email already exists.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO employees (name, email, phone, role, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("sssss", $name, $email, $phone, $role, $status);

            if ($stmt->execute()) {

                header("Location: employee.php?created=1");
                exit;

            } else {

                $error = "Something went wrong. Please try again.";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Employee</title>

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
        <h5 class="m-b-10">Create Employee</h5>
    </div>
</div>

<div class="main-content">
<div class="row">
<div class="col-lg-8">

<div class="card">
<div class="card-body">

<!-- ✅ ERROR ALERT -->
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">

    <div class="mb-3">
        <label class="form-label">Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars($name) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($email) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control"
               value="<?= htmlspecialchars($phone) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Role *</label>
        <select name="role" class="form-control" required>

            <option value="">Select Role</option>

            <option value="Developer" <?= $role=="Developer"?'selected':'' ?>>Developer</option>
            <option value="Designer" <?= $role=="Designer"?'selected':'' ?>>Designer</option>
            <option value="Manager"  <?= $role=="Manager"?'selected':'' ?>>Manager</option>
            <option value="QA"       <?= $role=="QA"?'selected':'' ?>>QA</option>

        </select>
    </div>

    <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="Active"   <?= $status=="Active"?'selected':'' ?>>Active</option>
            <option value="Inactive" <?= $status=="Inactive"?'selected':'' ?>>Inactive</option>
        </select>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="feather-user-plus me-2"></i> Create Employee
        </button>
    </div>

</form>

</div>
</div>

</div>
</div>
</div>

</div>

<?php include 'footer.php'; ?>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>