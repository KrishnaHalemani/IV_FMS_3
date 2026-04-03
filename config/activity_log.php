<?php

if (!function_exists('iv_ensure_activity_log_table')) {
    function iv_ensure_activity_log_table(mysqli $conn): bool
    {
        static $created = false;
        if ($created) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_role VARCHAR(20) NOT NULL,
            user_email VARCHAR(255) NOT NULL,
            action VARCHAR(120) NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activity_created_at (created_at),
            INDEX idx_activity_role (user_role)
        )";

        $created = (bool) $conn->query($sql);
        return $created;
    }
}

if (!function_exists('iv_log_activity')) {
    function iv_log_activity(mysqli $conn, string $action, ?string $details = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $role = (string) ($_SESSION['role'] ?? '');
        if ($role === '') {
            return false;
        }

        if (!iv_ensure_activity_log_table($conn)) {
            return false;
        }

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $email = (string) ($_SESSION['email'] ?? '');
        $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (strlen($userAgent) > 255) {
            $userAgent = substr($userAgent, 0, 255);
        }

        $stmt = $conn->prepare(
            "INSERT INTO activity_logs (user_id, user_role, user_email, action, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issssss",
            $userId,
            $role,
            $email,
            $action,
            $details,
            $ipAddress,
            $userAgent
        );

        return (bool) $stmt->execute();
    }
}
