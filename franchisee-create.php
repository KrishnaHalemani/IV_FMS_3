<?php
require_once __DIR__ . '/config/franchisees.php';

iv_require_master_session();

$error = '';
$form = [
    'franchisee_code' => 'FR-' . date('Ymd-His'),
    'franchisee_name' => '',
    'owner_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'status' => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = trim((string) ($_POST[$key] ?? $value));
    }

    if ($form['franchisee_code'] === '' || $form['franchisee_name'] === '') {
        $error = 'Franchisee code and name are required.';
    } else {
        $check = $conn->prepare("SELECT id FROM franchisees WHERE franchisee_code = ? OR (? <> '' AND email = ?) LIMIT 1");
        $check->bind_param("sss", $form['franchisee_code'], $form['email'], $form['email']);
        $check->execute();
        $duplicate = $check->get_result()->fetch_assoc();
        $check->close();

        if ($duplicate) {
            $error = 'Franchisee code or email already exists.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO franchisees (
                    franchisee_code, franchisee_name, owner_name, email, phone, address, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $createdBy = (int) $_SESSION['user_id'];
            $stmt->bind_param(
                "sssssssi",
                $form['franchisee_code'],
                $form['franchisee_name'],
                $form['owner_name'],
                $form['email'],
                $form['phone'],
                $form['address'],
                $form['status'],
                $createdBy
            );

            if ($stmt->execute()) {
                header('Location: franchisees.php?created=1');
                exit;
            }

            $error = 'Unable to create franchisee right now.';
        }
    }
}

include 'header.php';
?>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Create Franchisee</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="franchisees.php">Franchisees</a></li>
                    <li class="breadcrumb-item">Create</li>
                </ul>
            </div>
        </div>

        <div class="main-content">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Franchisee Code *</label>
                                        <input type="text" name="franchisee_code" class="form-control" value="<?= htmlspecialchars($form['franchisee_code']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="Active" <?= $form['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                            <option value="Inactive" <?= $form['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Franchisee Name *</label>
                                    <input type="text" name="franchisee_name" class="form-control" value="<?= htmlspecialchars($form['franchisee_name']) ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Owner Name</label>
                                        <input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars($form['owner_name']) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form['email']) ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($form['phone']) ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="4"><?= htmlspecialchars($form['address']) ?></textarea>
                                </div>

                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="franchisees.php" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Create Franchisee</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
