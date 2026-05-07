-- Email notification system migration
-- Run this after base schema import.

-- 1) Add users.email_notifications_enabled
-- For MySQL versions without IF NOT EXISTS on ADD COLUMN, use the manual ALTER fallback below.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER status;

-- Manual fallback if needed:
-- ALTER TABLE users ADD COLUMN email_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER status;

-- 2) Create email_logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_user_id INT UNSIGNED DEFAULT NULL,
    recipient_email   VARCHAR(255) NOT NULL,
    subject           VARCHAR(255) NOT NULL,
    template_name     VARCHAR(100) NOT NULL,
    related_type      VARCHAR(100) DEFAULT NULL,
    related_id        INT DEFAULT NULL,
    status            ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    error_message     TEXT DEFAULT NULL,
    sent_at           DATETIME DEFAULT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email_logs_recipient_user (recipient_user_id),
    INDEX idx_email_logs_recipient_email (recipient_email),
    INDEX idx_email_logs_status (status),
    INDEX idx_email_logs_template (template_name),
    INDEX idx_email_logs_related (related_type, related_id),
    INDEX idx_email_logs_created_at (created_at),
    CONSTRAINT fk_email_logs_user FOREIGN KEY (recipient_user_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
