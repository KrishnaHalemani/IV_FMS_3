<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

if (!isset($_GET['id'])) {
    die('Project ID missing');
}

$project_id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die('Project not found');
}

/* ===== Progress Calculation ===== */
$start = strtotime($project['start_date']);
$end   = strtotime($project['end_date']);
$today = time();

if ($today <= $start) {
    $progress = 0;
} elseif ($today >= $end) {
    $progress = 100;
} else {
    $progress = round((($today - $start) / ($end - $start)) * 100);
}

/* ===== TCPDF SETUP ===== */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Infinite Vision');
$pdf->SetAuthor('Infinite Vision');
$pdf->SetTitle('Project Report');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

/* ===== LOGO ===== */
$logoPath = __DIR__ . '/assets/images/Logo_IV.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 15, 35);
}

$pdf->Ln(25);

/* ===== HTML CONTENT ===== */
$html = '
<h2>Infinite Vision – Project Report</h2>
<hr>

<table cellpadding="6" cellspacing="0" border="1" width="100%">
<tr>
    <td width="30%"><strong>Project Name</strong></td>
    <td width="70%">'.$project['project_name'].'</td>
</tr>
<tr>
    <td><strong>Customer</strong></td>
    <td>'.$project['customer_name'].'</td>
</tr>
<tr>
    <td><strong>Project Type</strong></td>
    <td>'.ucfirst($project['project_type']).'</td>
</tr>
<tr>
    <td><strong>Billing Type</strong></td>
    <td>'.ucfirst($project['billing_type']).'</td>
</tr>
<tr>
    <td><strong>Status</strong></td>
    <td>'.ucfirst($project['project_status']).'</td>
</tr>
<tr>
    <td><strong>Project Hours</strong></td>
    <td>'.$project['project_hours'].' hrs</td>
</tr>
<tr>
    <td><strong>Start Date</strong></td>
    <td>'.$project['start_date'].'</td>
</tr>
<tr>
    <td><strong>End Date</strong></td>
    <td>'.$project['end_date'].'</td>
</tr>
<tr>
    <td><strong>Progress</strong></td>
    <td>'.$progress.'%</td>
</tr>
</table>

<br><br>

<h4>Project Description</h4>
<p>'.$project['description'].'</p>
';

$pdf->writeHTML($html, true, false, true, false, '');

/* ===== FORCE DOWNLOAD ===== */
$pdf->Output('project_'.$project_id.'.pdf', 'D');
exit;
