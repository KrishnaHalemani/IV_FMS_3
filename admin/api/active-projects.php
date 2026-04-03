<?php
require_once '../../config/db.php';

$sql = "SELECT * FROM projects WHERE project_status IN ('active', 'inprogress', 'In Progress') ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calculate progress
        $start = strtotime($row['start_date']);
        $end = strtotime($row['end_date']);
        $now = time();
        $progress = 0;
        if ($end > $start && $now > $start) {
            $progress = min(100, round((($now - $start) / ($end - $start)) * 100));
        } elseif ($now >= $end) {
            $progress = 100;
        }
?>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-text avatar-md bg-soft-primary text-primary">
                <?= strtoupper(substr($row['project_name'], 0, 1)) ?>
            </div>
            <div>
                <h6 class="mb-0 text-truncate-1-line"><?= htmlspecialchars($row['project_name']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($row['customer_name']) ?></small>
            </div>
        </div>
        <div class="text-end">
            <span class="fs-12 fw-semibold text-muted"><?= $progress ?>%</span>
        </div>
    </div>
<?php
    }
} else {
    echo '<p class="text-muted text-center py-3">No active projects found.</p>';
}
?>
