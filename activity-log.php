<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/config/activity_log.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (getRoleLevel($_SESSION['role']) < getRoleLevel('master')) {
    http_response_code(403);
    exit('Forbidden');
}

iv_ensure_activity_log_table($conn);

$rows = [];
$sql = "SELECT id, user_role, user_email, action, details, ip_address, created_at
        FROM activity_logs
        ORDER BY id DESC
        LIMIT 300";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

include 'header.php';
?>

<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card stretch stretch-full">
                        <div class="card-header">
                            <h5 class="mb-0">Master Admin Activity Log</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Role</th>
                                            <th>Email</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rows)): ?>
                                            <?php foreach ($rows as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                                                    <td><?= htmlspecialchars((string) $row['user_role']) ?></td>
                                                    <td><?= htmlspecialchars((string) $row['user_email']) ?></td>
                                                    <td><?= htmlspecialchars((string) $row['action']) ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['details'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['ip_address'] ?? '')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">No activity recorded yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</main>
