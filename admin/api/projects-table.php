<?php
require_once '../../config/db.php';

$sql = "SELECT * FROM projects ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Status Badge
        $statusClass = 'secondary';
        $status = strtolower($row['project_status']);
        if ($status == 'active' || $status == 'inprogress' || $status == 'in progress') $statusClass = 'primary';
        elseif ($status == 'finished') $statusClass = 'success';
        elseif ($status == 'declined') $statusClass = 'danger';
        elseif ($status == 'on hold') $statusClass = 'warning';

        // Progress Calculation
        $start = strtotime($row['start_date']);
        $end = strtotime($row['end_date']);
        $now = time();
        $progress = 0;
        if ($end > $start && $now > $start) {
            $progress = min(100, round((($now - $start) / ($end - $start)) * 100));
        } elseif ($now >= $end) {
            $progress = 100;
        }
        if ($status == 'finished') $progress = 100;

        // Budget (Mocking or calculating based on hours * rate if available, else showing hours)
        // Assuming $25/hr rate for demo if no budget column
        $budget = '$' . number_format($row['project_hours'] * 25, 2); 
?>
    <tr>
        <td>
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                    <?= strtoupper(substr($row['project_name'], 0, 1)) ?>
                </div>
                <div>
                    <a href="projects-view.php?id=<?= $row['id'] ?>" class="d-block fw-bold text-dark">
                        <?= htmlspecialchars($row['project_name']) ?>
                    </a>
                    <span class="fs-12 text-muted"><?= htmlspecialchars($row['customer_name']) ?></span>
                </div>
            </div>
        </td>
        <td>
            <span class="fw-bold text-dark"><?= $budget ?></span>
            <div class="fs-11 text-muted"><?= $row['project_hours'] ?> Hrs</div>
        </td>
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1 ht-5">
                    <div class="progress-bar bg-<?= $statusClass ?>" role="progressbar" style="width: <?= $progress ?>%"></div>
                </div>
                <span class="fs-12 fw-medium text-muted"><?= $progress ?>%</span>
            </div>
        </td>
        <td>
            <span class="badge bg-soft-<?= $statusClass ?> text-<?= $statusClass ?>">
                <?= ucfirst($row['project_status']) ?>
            </span>
        </td>
        <td class="text-end">
            <div class="dropdown">
                <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown">
                    <i class="feather-more-vertical"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="projects-view.php?id=<?= $row['id'] ?>" class="dropdown-item"><i class="feather-eye me-2"></i>View</a>
                    <a href="javascript:void(0);" class="dropdown-item"><i class="feather-edit me-2"></i>Edit</a>
                    <div class="dropdown-divider"></div>
                    <a href="javascript:void(0);" class="dropdown-item text-danger"><i class="feather-trash-2 me-2"></i>Delete</a>
                </div>
            </div>
        </td>
    </tr>
<?php
    }
} else {
    echo '<tr><td colspan="5" class="text-center py-3">No projects found.</td></tr>';
}
?>
