<?php
require_once __DIR__ . '/config/db.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Employee not found");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name  = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role  = $_POST['role'];

    $update = $conn->prepare("
        UPDATE employees 
        SET name = ?, email = ?, phone = ?, role = ?
        WHERE id = ?
    ");
    $update->bind_param("ssssi", $name, $email, $phone, $role, $id);
    $update->execute();

    header("Location: employee.php?updated=1");
    exit;
}

$currentRole = $data['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Employee</title>

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
            <h5 class="m-b-10">Edit Employee</h5>
        </div>
    </div>

    <div class="main-content">
        <div class="row justify-content-center">

            <div class="col-lg-6">

                <div class="card">
                    <div class="card-body">

                        <form method="POST">

                            <!-- NAME -->
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?= htmlspecialchars($data['name']) ?>" required>
                            </div>

                            <!-- EMAIL -->
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($data['email']) ?>" required>
                            </div>

                            <!-- PHONE -->
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($data['phone']) ?>">
                            </div>

                            <!-- ROLE -->
                            <div class="mb-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="">Select Role</option>

                                    <option value="Developer" <?= $currentRole == 'Developer' ? 'selected' : '' ?>>
                                        Developer
                                    </option>

                                    <option value="Designer" <?= $currentRole == 'Designer' ? 'selected' : '' ?>>
                                        Designer
                                    </option>

                                    <option value="Manager" <?= $currentRole == 'Manager' ? 'selected' : '' ?>>
                                        Manager
                                    </option>

                                    <option value="QA" <?= $currentRole == 'QA' ? 'selected' : '' ?>>
                                        QA
                                    </option>
                                </select>
                            </div>

                            <!-- BUTTONS -->
                            <div class="d-grid gap-2">

                                <button type="submit" class="btn btn-primary">
                                    Update Employee
                                </button>

                                <a href="employee.php" class="btn btn-secondary">
                                    Cancel
                                </a>

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