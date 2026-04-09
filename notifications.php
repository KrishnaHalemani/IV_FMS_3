<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/config/notifications.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        iv_mark_all_notifications_read($conn, $currentUserId);
    } elseif (isset($_POST['notification_id'])) {
        iv_mark_notification_read($conn, $currentUserId, (int) $_POST['notification_id']);
    }

    header('Location: notifications.php');
    exit;
}

$unreadNotificationCount = iv_count_unread_notifications($conn, $currentUserId);
$notifications = iv_fetch_notifications($conn, $currentUserId);

include 'header.php';
?>

<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Notifications</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Notifications</li>
                </ul>
            </div>
        </div>

        <?php include __DIR__ . '/partials/notifications-content.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
