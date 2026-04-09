<?php
require_once __DIR__ . '/config/franchisees.php';

iv_require_master_session();

$franchiseeId = (int) ($_GET['id'] ?? 0);
if ($franchiseeId <= 0) {
    exit('Franchisee ID missing');
}

$flash = isset($_GET['updated']) ? 'Franchisee updated successfully.' : null;
$error = '';

$stmt = $conn->prepare("SELECT * FROM franchisees WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $franchiseeId);
$stmt->execute();
$franchisee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$franchisee) {
    exit('Franchisee not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_franchisee'])) {
    $franchiseeCode = trim((string) ($_POST['franchisee_code'] ?? ''));
    $franchiseeName = trim((string) ($_POST['franchisee_name'] ?? ''));
    $ownerName = trim((string) ($_POST['owner_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'Active'));

    if ($franchiseeCode === '' || $franchiseeName === '') {
        $error = 'Franchisee code and name are required.';
    } else {
        $duplicate = $conn->prepare("
            SELECT id
            FROM franchisees
            WHERE id <> ?
              AND (franchisee_code = ? OR (? <> '' AND email = ?))
            LIMIT 1
        ");
        $duplicate->bind_param("isss", $franchiseeId, $franchiseeCode, $email, $email);
        $duplicate->execute();
        $hasDuplicate = $duplicate->get_result()->fetch_assoc();
        $duplicate->close();

        if ($hasDuplicate) {
            $error = 'Franchisee code or email already exists.';
        } else {
            $update = $conn->prepare("
                UPDATE franchisees
                SET franchisee_code = ?, franchisee_name = ?, owner_name = ?, email = ?, phone = ?, address = ?, status = ?
                WHERE id = ?
            ");
            $update->bind_param("sssssssi", $franchiseeCode, $franchiseeName, $ownerName, $email, $phone, $address, $status, $franchiseeId);
            $update->execute();
            $update->close();

            header('Location: franchisee-view.php?id=' . $franchiseeId . '&updated=1');
            exit;
        }
    }
}

$projectRows = [];
$projectStmt = $conn->prepare("
    SELECT id, project_name, project_code, project_status, estimated_budget, end_date
    FROM projects
    WHERE franchisee_id = ?
    ORDER BY created_at DESC, id DESC
");
$projectStmt->bind_param("i", $franchiseeId);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
while ($projectResult && $row = $projectResult->fetch_assoc()) {
    $projectRows[] = $row;
}
$projectStmt->close();

include 'header.php';
?>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10"><?= htmlspecialchars((string) $franchisee['franchisee_name']) ?></h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="franchisees.php">Franchisees</a></li>
                    <li class="breadcrumb-item">Open</li>
                </ul>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="main-content">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="save_franchisee" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Franchisee Code *</label>
                                        <input type="text" name="franchisee_code" class="form-control" value="<?= htmlspecialchars((string) $franchisee['franchisee_code']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="Active" <?= $franchisee['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                            <option value="Inactive" <?= $franchisee['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Franchisee Name *</label>
                                    <input type="text" name="franchisee_name" class="form-control" value="<?= htmlspecialchars((string) $franchisee['franchisee_name']) ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Owner Name</label>
                                        <input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars((string) ($franchisee['owner_name'] ?? '')) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($franchisee['email'] ?? '')) ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($franchisee['phone'] ?? '')) ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="4"><?= htmlspecialchars((string) ($franchisee['address'] ?? '')) ?></textarea>
                                </div>

                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="franchisees.php" class="btn btn-light">Back</a>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Assigned Projects</h5>
                            <?php if ($projectRows === []): ?>
                                <div class="text-muted">No projects are linked to this franchisee yet.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($projectRows as $projectRow): ?>
                                        <a href="projects-view.php?id=<?= (int) $projectRow['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $projectRow['project_name']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string) ($projectRow['project_code'] ?: 'No code')) ?></div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="small"><?= htmlspecialchars((string) $projectRow['project_status']) ?></div>
                                                    <div class="small text-muted"><?= number_format((float) ($projectRow['estimated_budget'] ?? 0), 2) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
