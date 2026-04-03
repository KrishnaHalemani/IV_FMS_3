<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$billable = 0;
$unbillable = 0;

// Calculate Total Project Hours (Billable)
$sql = "SELECT SUM(project_hours) as total FROM projects";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $billable = (int)$row['total'];
}

// Since we don't have a specific 'unbillable' column in the provided schema,
// we will set it to 0 or a calculated overhead for visualization purposes.
// For now, let's assume 0 if no specific data exists.
$unbillable = 0; 

echo json_encode(['billable' => $billable, 'unbillable' => $unbillable]);
?>
