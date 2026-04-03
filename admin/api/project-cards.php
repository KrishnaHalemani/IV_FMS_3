<?php
require_once '../../config/db.php';

// Fetch 4 recent projects
$sql = "SELECT * FROM projects ORDER BY created_at DESC LIMIT 4";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calculate days left
        $deadline = strtotime($row['end_date']);
        $now = time();
        $daysLeft = ceil(($deadline - $now) / (60 * 60 * 24));
        $daysText = ($daysLeft > 0) ? $daysLeft . " days left" : "Overdue";
        $daysClass = ($daysLeft > 0) ? "text-muted" : "text-danger";

        // Calculate progress (simple time-based or random for demo if no tasks)
        $start = strtotime($row['start_date']);
        $totalDays = ceil(($deadline - $start) / (60 * 60 * 24));
        $elapsed = ceil(($now - $start) / (60 * 60 * 24));
        
        $progress = 0;
        if ($totalDays > 0) {
            $progress = min(100, max(0, round(($elapsed / $totalDays) * 100)));
        }
        if ($row['project_status'] == 'finished') $progress = 100;

?>
    <div class="col-xxl-3 col-md-6">
        <div class="card-body border border-dashed border-gray-5 rounded-3 position-relative">
            <div class="hstack justify-content-between gap-4">
                <div>
                    <h6 class="fs-14 text-truncate-1-line"><?= htmlspecialchars($row['project_name']) ?></h6>
                    <div class="fs-12 <?= $daysClass ?>">
                        <span class="text-dark fw-medium">Deadline:</span> <?= $daysText ?>
                    </div>
                </div>
                <div style="width: 50px; height: 50px;">
                    <!-- Simple CSS Conic Gradient for Progress -->
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-11" 
                         style="width: 100%; height: 100%; background: conic-gradient(#3454d1 <?= $progress ?>%, #e9ecef 0); border-radius: 50%;">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 80%; height: 80%;">
                            <?= $progress ?>%
                        </div>
                    </div>
                </div>
            </div>
            <div class="badge bg-gray-200 text-dark project-mini-card-badge"><?= ucfirst($row['project_status']) ?></div>
        </div>
    </div>
<?php
    }
} else {
    echo '<div class="col-12 text-center text-muted">No recent projects found.</div>';
}
?>
