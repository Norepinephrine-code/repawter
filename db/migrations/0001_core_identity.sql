-- =============================================================================
--  0001_core_identity
--  Adds: barangays, users
--
--  These two tables sit at the root of the foreign-key graph — almost every
--  other table references `users`, and several reference `barangays` — so they
--  must exist before anything else.
--
--  `users.created_by` is self-referential: staff and admin accounts are
--  provisioned manually by an existing admin, so the row records who created
--  it. Residents self-register and leave it NULL.
-- =============================================================================

SET NAMES utf8mb4;

-- Barangays are the geographic unit the whole system is organised around:
-- reports, announcements and user profiles are all scoped to one.
CREATE TABLE IF NOT EXISTS `barangays` (
    `id`           BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(120)       NOT NULL,
    `municipality` VARCHAR(120)       NULL,
    `province`     VARCHAR(120)       NULL,
    `is_active`    TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Barangay names repeat across municipalities, so neither column is unique alone.
    UNIQUE KEY `uq_barangay_name_muni` (`name`, `municipality`),
    KEY `idx_barangay_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One table for all four roles. `role` drives RBAC (see app/core/rbac.php) and
-- `account_status` lets officials flag or suspend an account without deleting it.
CREATE TABLE IF NOT EXISTS `users` (
    `id`                BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `role`              ENUM('community_resident','barangay_official','welfare_org','system_admin')
                                           NOT NULL DEFAULT 'community_resident',
    `account_status`    ENUM('active','flagged','suspended')
                                           NOT NULL DEFAULT 'active',
    `email`             VARCHAR(255)       NOT NULL,
    -- password_hash() output; 255 chars leaves room for future, longer algorithms.
    `password_hash`     VARCHAR(255)       NOT NULL,
    `full_name`         VARCHAR(150)       NOT NULL,
    `contact_number`    VARCHAR(30)        NULL,
    `barangay_id`       BIGINT UNSIGNED    NULL,
    -- Only meaningful for welfare_org accounts.
    `organization_name` VARCHAR(150)       NULL,
    `flagged_reason`    VARCHAR(255)       NULL,
    `last_login_at`     DATETIME           NULL,
    `created_by`        BIGINT UNSIGNED    NULL,
    `created_at`        DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_account_status` (`account_status`),
    KEY `idx_users_barangay_id` (`barangay_id`),
    -- SET NULL, not CASCADE: deactivating a barangay must never delete residents.
    CONSTRAINT `fk_users_barangay` FOREIGN KEY (`barangay_id`)
        REFERENCES `barangays` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
