<?php
if (!function_exists('iv_ensure_notifications_table')) {
    function iv_ensure_notifications_table(mysqli $conn): bool
    {
        static $created = false;
        if ($created) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_notifications_user_read (user_id, is_read),
            INDEX idx_notifications_created_at (created_at),
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )";

        $created = (bool) $conn->query($sql);
        return $created;
    }
}

if (!function_exists('iv_create_notification')) {
    function iv_create_notification(
        mysqli $conn,
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $createdBy = null
    ): bool {
        if (!iv_ensure_notifications_table($conn)) {
            return false;
        }

        $stmt = $conn->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("issssi", $userId, $type, $title, $message, $link, $createdBy);
        $ok = (bool) $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('iv_create_notifications_for_users')) {
    function iv_create_notifications_for_users(
        mysqli $conn,
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $createdBy = null
    ): void {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        foreach ($userIds as $userId) {
            if ($userId > 0) {
                iv_create_notification($conn, $userId, $type, $title, $message, $link, $createdBy);
            }
        }
    }
}

if (!function_exists('iv_count_unread_notifications')) {
    function iv_count_unread_notifications(mysqli $conn, int $userId): int
    {
        if (!iv_ensure_notifications_table($conn)) {
            return 0;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('iv_fetch_notifications')) {
    function iv_fetch_notifications(mysqli $conn, int $userId, int $limit = 50): array
    {
        if (!iv_ensure_notifications_table($conn)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $stmt = $conn->prepare(
            "SELECT n.*, creator.username AS creator_name
             FROM notifications n
             LEFT JOIN users creator ON creator.id = n.created_by
             WHERE n.user_id = ?
             ORDER BY n.is_read ASC, n.created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('iv_mark_notification_read')) {
    function iv_mark_notification_read(mysqli $conn, int $userId, int $notificationId): bool
    {
        if (!iv_ensure_notifications_table($conn)) {
            return false;
        }

        $stmt = $conn->prepare(
            "UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notificationId, $userId);
        $ok = (bool) $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('iv_mark_all_notifications_read')) {
    function iv_mark_all_notifications_read(mysqli $conn, int $userId): bool
    {
        if (!iv_ensure_notifications_table($conn)) {
            return false;
        }

        $stmt = $conn->prepare(
            "UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param("i", $userId);
        $ok = (bool) $stmt->execute();
        $stmt->close();

        return $ok;
    }
}
?>
