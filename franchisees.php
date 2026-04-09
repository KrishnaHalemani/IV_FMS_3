<?php
require_once __DIR__ . '/config/franchisees.php';

iv_require_master_session();

$flash = null;
if (isset($_GET['created'])) {
    $flash = 'Franchisee created successfully.';
} elseif (isset($_GET['updated'])) {
    $flash = 'Franchisee updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $flash = 'Franchisee deleted successfully.';
}

$rows = [];
$sql = "
    SELECT
        f.*,
        COALESCE(u.username, 'System') AS creator_name,
        COUNT(p.id) AS project_count,
        COALESCE(SUM(p.estimated_budget), 0) AS total_budget
    FROM franchisees f
    LEFT JOIN users u ON u.id = f.created_by
    LEFT JOIN projects p ON p.franchisee_id = f.id
    GROUP BY
        f.id,
        f.franchisee_code,
        f.franchisee_name,
        f.owner_name,
        f.email,
        f.phone,
        f.address,
        f.status,
        f.created_by,
        f.created_at,
        f.updated_at,
        u.username
    ORDER BY f.created_at DESC, f.id DESC
";
$result = $conn->query($sql);
while ($result && $row = $result->fetch_assoc()) {
    $rows[] = $row;
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
                    <h5 class="m-b-10">Franchisees</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Franchisees</li>
                </ul>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card stretch stretch-full">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Franchisee Directory</h5>
                                <p class="text-muted mb-0">Manage franchisee ownership and track project allocation.</p>
                            </div>
                            <a href="franchisee-create.php" class="btn btn-primary">Add Franchisee</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="franchiseeList">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Franchisee</th>
                                            <th>Owner</th>
                                            <th>Contact</th>
                                            <th>Projects</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($rows === []): ?>
                                            <tr>
                                                <td></td>
                                                <td class="text-center py-4 text-muted">No franchisees added yet.</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php foreach ($rows as $row): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $row['franchisee_code']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars(date('d M Y', strtotime((string) $row['created_at']))) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $row['franchisee_name']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars((string) ($row['address'] ?: 'No address added')) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string) ($row['owner_name'] ?: '-')) ?></td>
                                                <td>
                                                    <div><?= htmlspecialchars((string) ($row['email'] ?: '-')) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars((string) ($row['phone'] ?: '-')) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= (int) $row['project_count'] ?> projects</div>
                                                    <div class="text-muted small">Budget <?= number_format((float) $row['total_budget'], 2) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $row['status'] === 'Active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' ?>">
                                                        <?= htmlspecialchars((string) $row['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars((string) $row['creator_name']) ?></td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a href="franchisee-view.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-light">Open</a>
                                                        <form method="post" action="franchisee-delete.php" onsubmit="return confirm('Delete this franchisee? Linked projects will become unassigned from franchisee.');">
                                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="assets/vendors/js/dataTables.min.js"></script>
<script src="assets/vendors/js/dataTables.bs5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#franchiseeList').DataTable({
            destroy: true,
            autoWidth: false
        });
    });
</script>

</body>
</html>
