<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$data = [];
$labels = [];

// Get data for the last 6 months
for ($i = 5; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    
    // Count projects created in this month
    $sql = "SELECT COUNT(*) as count FROM projects WHERE created_at BETWEEN '$monthStart 00:00:00' AND '$monthEnd 23:59:59'";
    $result = $conn->query($sql);
    $count = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int)$row['count'];
    }
    
    $data[] = $count;
    $labels[] = $monthLabel;
}

echo json_encode(['labels' => $labels, 'data' => $data]);
?>
