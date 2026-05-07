-- ============================================================
-- ResQFood — Full Database Schema
-- Database : resqfood_db
-- Charset  : utf8mb4 / utf8mb4_unicode_ci
-- Engine   : InnoDB (FK support, transactions)
-- Compatible: MySQL 5.7+ / MariaDB 10.3+
--
-- Import via phpMyAdmin or:
--   mysql -u root -p < database/resqfood_schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS resqfood_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE resqfood_db;

-- Disable FK checks during schema creation for clean ordering
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- TABLE: users
-- Core accounts for all platform roles.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(120)     NOT NULL,
    email         VARCHAR(180)     NOT NULL,
    phone         VARCHAR(25)      DEFAULT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    role          ENUM(
                    'business',
                    'general_user',
                    'charity',
                    'admin'
                  ) NOT NULL,
    status        ENUM(
                    'active',
                    'inactive',
                    'suspended',
                    'pending'
                  ) NOT NULL DEFAULT 'pending',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE  KEY uk_users_email  (email),
    INDEX   idx_users_role      (role),
    INDEX   idx_users_status    (status)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Core user accounts — all roles share this table';


-- ============================================================
-- TABLE: business_profiles
-- Extended details for food-business accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS business_profiles (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    business_name       VARCHAR(160) NOT NULL,
    business_type       VARCHAR(80)  DEFAULT NULL COMMENT 'e.g. restaurant, bakery, cafe, supermarket',
    address             TEXT         DEFAULT NULL,
    city                VARCHAR(80)  DEFAULT NULL,
    description         TEXT         DEFAULT NULL,
    pickup_notes        TEXT         DEFAULT NULL COMMENT 'Default pickup instructions shown on listings',
    verification_status ENUM('pending','verified','rejected')
                         NOT NULL DEFAULT 'pending',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_bp_user (user_id),
    INDEX  idx_bp_city    (city),

    CONSTRAINT fk_bp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for food business accounts';


-- ============================================================
-- TABLE: charity_profiles
-- Extended details for charity / NGO accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS charity_profiles (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    organization_name   VARCHAR(160) NOT NULL,
    contact_person      VARCHAR(120) DEFAULT NULL,
    address             TEXT         DEFAULT NULL,
    city                VARCHAR(80)  DEFAULT NULL,
    description         TEXT         DEFAULT NULL,
    verification_status ENUM('pending','verified','rejected')
                         NOT NULL DEFAULT 'pending',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_cp_user (user_id),
    INDEX  idx_cp_city    (city),

    CONSTRAINT fk_cp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for charity / NGO accounts';


-- ============================================================
-- TABLE: food_listings
-- Surplus food items posted by business accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS food_listings (
    id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    business_user_id INT UNSIGNED   NOT NULL,
    title            VARCHAR(200)   NOT NULL,
    category         VARCHAR(80)    DEFAULT NULL COMMENT 'e.g. bakery, produce, dairy, prepared meals',
    quantity         DECIMAL(8,2)   NOT NULL DEFAULT 0.00,
    unit             VARCHAR(30)    NOT NULL DEFAULT 'portions'
                                    COMMENT 'e.g. kg, portions, bags, boxes',
    description      TEXT           DEFAULT NULL,
    pickup_address   TEXT           DEFAULT NULL
                                    COMMENT 'Override if different from business profile address',
    pickup_location_label VARCHAR(150) DEFAULT NULL
                                    COMMENT 'Optional pickup hint like Main gate or Reception desk',
    pickup_latitude  DECIMAL(10,8) DEFAULT NULL
                                    COMMENT 'Pinned map latitude for pickup point',
    pickup_longitude DECIMAL(11,8) DEFAULT NULL
                                    COMMENT 'Pinned map longitude for pickup point',
    pickup_start     DATETIME       NOT NULL COMMENT 'Earliest pickup time',
    pickup_end       DATETIME       NOT NULL COMMENT 'Latest pickup time',
    expiry_time      DATETIME       DEFAULT NULL COMMENT 'When the food expires — for urgency display',
    status           ENUM(
                       'available',
                       'reserved',
                       'collected',
                       'expired',
                       'cancelled'
                     ) NOT NULL DEFAULT 'available',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_fl_status        (status),
    INDEX idx_fl_business_user (business_user_id),
    INDEX idx_fl_pickup_start  (pickup_start),
    INDEX idx_fl_category      (category),
    INDEX idx_fl_created       (created_at),

    CONSTRAINT fk_fl_business FOREIGN KEY (business_user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Surplus food listings posted by business accounts';


-- ============================================================
-- TABLE: listing_images
-- Photos attached to a food listing (multiple allowed).
-- ============================================================
CREATE TABLE IF NOT EXISTS listing_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(300) NOT NULL COMMENT 'Path relative to /uploads/listings/',
    is_primary TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = main display image',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_li_listing (listing_id),

    CONSTRAINT fk_li_listing FOREIGN KEY (listing_id)
        REFERENCES food_listings(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Images attached to food listings';


-- ============================================================
-- TABLE: reservations
-- A user or charity reserves a food listing.
-- ============================================================
CREATE TABLE IF NOT EXISTS reservations (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id         INT UNSIGNED NOT NULL,
    reserved_by        INT UNSIGNED NOT NULL COMMENT 'user_id of general_user or charity',
    reservation_status ENUM(
                         'reserved',
                         'cancelled',
                         'collected',
                         'expired',
                         'no_show'
                       ) NOT NULL DEFAULT 'reserved',
    pickup_code        VARCHAR(12)  NOT NULL COMMENT 'Short code for in-person verification at pickup',
    reserved_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    collected_at       DATETIME     DEFAULT NULL COMMENT 'Set when business marks as collected',
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_res_listing_user (listing_id, reserved_by)
               COMMENT 'One active reservation per user per listing',
    INDEX idx_res_status           (reservation_status),
    INDEX idx_res_reserved_by      (reserved_by),
    INDEX idx_res_listing          (listing_id),

    CONSTRAINT fk_res_listing FOREIGN KEY (listing_id)
        REFERENCES food_listings(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_res_user FOREIGN KEY (reserved_by)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Reservations made by users or charities on food listings';


-- ============================================================
-- TABLE: reservation_status_logs
-- Full audit trail of every reservation status change.
-- ============================================================
CREATE TABLE IF NOT EXISTS reservation_status_logs (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED NOT NULL,
    old_status     VARCHAR(30)  DEFAULT NULL COMMENT 'NULL for the initial creation entry',
    new_status     VARCHAR(30)  NOT NULL,
    changed_by     INT UNSIGNED DEFAULT NULL COMMENT 'user_id who triggered the change; NULL for system',
    note           TEXT         DEFAULT NULL COMMENT 'Free-text reason or context',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_rsl_reservation (reservation_id),

    CONSTRAINT fk_rsl_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rsl_changed_by FOREIGN KEY (changed_by)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail of every reservation status transition';


-- ============================================================
-- TABLE: reports
-- Reports submitted by users about listings or platform issues.
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_by     INT UNSIGNED NOT NULL,
    listing_id    INT UNSIGNED DEFAULT NULL COMMENT 'Optional — may be a general platform report',
    reported_user INT UNSIGNED DEFAULT NULL COMMENT 'Optional — report about a specific user',
    reason        VARCHAR(120) NOT NULL,
    details       TEXT         DEFAULT NULL,
    report_status ENUM(
                    'open',
                    'under_review',
                    'resolved',
                    'dismissed'
                  ) NOT NULL DEFAULT 'open',
    reviewed_by   INT UNSIGNED DEFAULT NULL COMMENT 'Admin user_id who handled the report',
    admin_note    TEXT         DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_rep_status     (report_status),
    INDEX idx_rep_report_by  (report_by),
    INDEX idx_rep_listing    (listing_id),

    CONSTRAINT fk_rep_report_by   FOREIGN KEY (report_by)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rep_listing     FOREIGN KEY (listing_id)
        REFERENCES food_listings(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_rep_reported_user FOREIGN KEY (reported_user)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_rep_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User-submitted reports about listings or platform conduct';


-- ============================================================
-- TABLE: impact_records
-- Environmental / social impact logged when a listing is collected.
-- ============================================================
CREATE TABLE IF NOT EXISTS impact_records (
    id                    INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    listing_id            INT UNSIGNED   NOT NULL,
    reservation_id        INT UNSIGNED   DEFAULT NULL COMMENT 'Linked reservation if applicable',
    estimated_meals_saved DECIMAL(8,2)   NOT NULL DEFAULT 0.00,
    estimated_kg_saved    DECIMAL(8,3)   NOT NULL DEFAULT 0.000
                                         COMMENT 'Kilograms of food diverted from waste',
    estimated_co2_reduced DECIMAL(8,3)   NOT NULL DEFAULT 0.000
                                         COMMENT 'kg CO2-equivalent saved',
    recorded_at           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_ir_listing  (listing_id),
    INDEX idx_ir_recorded (recorded_at),

    CONSTRAINT fk_ir_listing FOREIGN KEY (listing_id)
        REFERENCES food_listings(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ir_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations(id) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Environmental and social impact data per completed collection';


-- ============================================================
-- TABLE: notifications
-- In-platform notifications delivered to individual users.
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(160) NOT NULL,
    message    TEXT         NOT NULL,
    link       VARCHAR(300) DEFAULT NULL COMMENT 'Optional internal URL for the notification',
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_notif_user    (user_id),
    INDEX idx_notif_is_read (is_read),
    INDEX idx_notif_created (created_at),

    CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='In-platform notifications sent to users';


-- ============================================================
-- TABLE: audit_logs
-- System-wide event log for admin oversight and debugging.
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED DEFAULT NULL COMMENT 'NULL for system-generated events',
    action     VARCHAR(160) NOT NULL    COMMENT 'Short machine-readable label, e.g. user_login',
    details    TEXT         DEFAULT NULL COMMENT 'JSON or human-readable context string',
    ip_address VARCHAR(45)  DEFAULT NULL COMMENT 'Supports both IPv4 and IPv6',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_al_user_id (user_id),
    INDEX idx_al_action  (action),
    INDEX idx_al_created (created_at),

    CONSTRAINT fk_al_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='System-wide audit trail for admin oversight';


-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- SEED: Default admin account
-- Email   : admin@resqfood.local
-- Password: Admin@1234   ← change immediately after first login
-- Hash    : bcrypt cost 12
-- ============================================================
INSERT IGNORE INTO users
    (full_name, email, phone, password_hash, role, status)
VALUES (
    'Platform Admin',
    'admin@resqfood.local',
    NULL,
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    'admin',
    'active'
);
-- NOTE: The hash above is a placeholder. Run this PHP snippet to generate a fresh one:
--   echo password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
-- Then UPDATE users SET password_hash = '<output>' WHERE email = 'admin@resqfood.local';

-- ============================================================
-- PATCH SECTION: Existing database upgrades
-- Use this section when upgrading an existing DB without reimporting.
-- ============================================================

SET @db_name := DATABASE();
SET @table_name := 'food_listings';

SET @has_pickup_location_label := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = @table_name
      AND COLUMN_NAME = 'pickup_location_label'
);
SET @sql := IF(@has_pickup_location_label = 0,
    'ALTER TABLE food_listings ADD COLUMN pickup_location_label VARCHAR(150) NULL AFTER pickup_address',
    'SELECT "pickup_location_label already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_pickup_latitude := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = @table_name
      AND COLUMN_NAME = 'pickup_latitude'
);
SET @sql := IF(@has_pickup_latitude = 0,
    'ALTER TABLE food_listings ADD COLUMN pickup_latitude DECIMAL(10,8) NULL AFTER pickup_location_label',
    'SELECT "pickup_latitude already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_pickup_longitude := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = @table_name
      AND COLUMN_NAME = 'pickup_longitude'
);
SET @sql := IF(@has_pickup_longitude = 0,
    'ALTER TABLE food_listings ADD COLUMN pickup_longitude DECIMAL(11,8) NULL AFTER pickup_latitude',
    'SELECT "pickup_longitude already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
