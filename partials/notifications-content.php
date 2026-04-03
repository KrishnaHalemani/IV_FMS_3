<?php
if (!isset($notifications, $unreadNotificationCount)) {
    throw new RuntimeException('Notifications page requires notifications and unread count.');
}
?>

<style>
    .iv-notifications-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
    }

    .iv-notification-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        padding: 18px;
        background: #fff;
    }

    .iv-notification-item + .iv-notification-item {
        margin-top: 14px;
    }

    .iv-notification-item.unread {
        border-color: rgba(37, 99, 235, 0.28);
        box-shadow: 0 10px 28px rgba(37, 99, 235, 0.08);
    }

    .iv-notification-pill {
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }
</style>

<div class="main-content">
    <div class="card iv-notifications-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">Notifications</h4>
                    <p class="text-muted mb-0">Assignments, status updates, and account actions will show up here.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-soft-danger text-danger"><?= number_format($unreadNotificationCount) ?> unread</span>
                    <form method="post" action="">
                        <input type="hidden" name="mark_all_read" value="1">
                        <button type="submit" class="btn btn-light">Mark All Read</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($notifications !== []): ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="iv-notification-item <?= (int) $notification['is_read'] === 0 ? 'unread' : '' ?>">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="iv-notification-pill"><?= htmlspecialchars((string) $notification['type']) ?></span>
                            <?php if ((int) $notification['is_read'] === 0): ?>
                                <span class="badge bg-soft-primary text-primary">New</span>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-2"><?= htmlspecialchars((string) $notification['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars((string) $notification['message']) ?></p>
                        <div class="fs-12 text-muted">
                            <?= date('d M Y, h:i A', strtotime((string) $notification['created_at'])) ?>
                            <?php if (!empty($notification['creator_name'])): ?>
                                • by <?= htmlspecialchars((string) $notification['creator_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <?php if (!empty($notification['link'])): ?>
                            <a href="<?= htmlspecialchars((string) $notification['link']) ?>" class="btn btn-primary btn-sm">Open</a>
                        <?php endif; ?>
                        <?php if ((int) $notification['is_read'] === 0): ?>
                            <form method="post" action="">
                                <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                                <button type="submit" class="btn btn-light btn-sm">Mark Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card iv-notifications-card">
            <div class="card-body p-4 text-center">
                <h5 class="mb-2">No notifications yet</h5>
                <p class="text-muted mb-0">Once users are created or projects are assigned and updated, this inbox will start filling up.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
