-- =============================================================================
--  0004_cases_and_pets
--  Adds: pets, cases, case_updates
--        + the deferred foreign key pets.case_id → cases.id
--
--  `pets` and `cases` reference each other, which no single CREATE TABLE order
--  can satisfy:
--
--      cases.pet_profile_id → pets.id      (the public profile for this case)
--      pets.case_id         → cases.id     (the case this animal came from)
--
--  So `pets` is created first without its case FK, then `cases`, then the
--  missing constraint is added by ALTER at the end of this migration.
-- =============================================================================

SET NAMES utf8mb4;

-- A publishable animal profile for the adoption gallery. May originate from a
-- rescue case (case_id set) or be entered directly by a shelter (case_id NULL).
CREATE TABLE IF NOT EXISTS `pets` (
    `id`               BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- FK added at the bottom of this file, once `cases` exists.
    `case_id`          BIGINT UNSIGNED    NULL,
    `created_by`       BIGINT UNSIGNED    NOT NULL,
    `name`             VARCHAR(80)        NOT NULL,
    `animal_type`      ENUM('dog','cat','other')
                                          NOT NULL DEFAULT 'dog',
    `species_other`    VARCHAR(80)        NULL,
    `breed`            VARCHAR(80)        NULL,
    `sex`              ENUM('male','female','unknown')
                                          NOT NULL DEFAULT 'unknown',
    -- Months rather than years: most rescues are puppies and kittens.
    `approx_age_months` INT UNSIGNED      NULL,
    `size`             ENUM('small','medium','large')
                                          NULL,
    `color`            VARCHAR(80)        NULL,
    -- Nullable tri-state: NULL means "not yet assessed", not "no".
    `is_vaccinated`    TINYINT(1)         NULL,
    `is_neutered`      TINYINT(1)         NULL,
    `description`      TEXT               NULL,
    `photo_path`       VARCHAR(255)       NULL,
    -- Only 'available' pets appear in the public gallery.
    `status`           ENUM('draft','available','pending_adoption','adopted','archived')
                                          NOT NULL DEFAULT 'draft',
    `published_at`     DATETIME           NULL,
    `created_at`       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pets_status`      (`status`),
    KEY `idx_pets_animal_type` (`animal_type`),
    KEY `idx_pets_case_id`     (`case_id`),
    CONSTRAINT `fk_pets_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tracks one animal from verified report through to a final outcome.
CREATE TABLE IF NOT EXISTS `cases` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `report_id`      BIGINT UNSIGNED    NOT NULL,
    -- Human-readable reference shown to staff and printed on reports.
    `case_number`    VARCHAR(30)        NOT NULL,
    `status`         ENUM('open','triaged','rescued','under_care','ready_for_adoption','fostered','adopted','released','deceased','closed')
                                        NOT NULL DEFAULT 'open',
    `animal_type`    ENUM('dog','cat','other')
                                        NOT NULL DEFAULT 'dog',
    `animal_name`    VARCHAR(80)        NULL,
    `managed_by`     BIGINT UNSIGNED    NULL,
    `pet_profile_id` BIGINT UNSIGNED    NULL,
    `resolution`     VARCHAR(120)       NULL,
    `resolution_notes` TEXT             NULL,
    `opened_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_at`      DATETIME           NULL,
    `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- At most one case per report.
    UNIQUE KEY `uq_cases_report_id`   (`report_id`),
    UNIQUE KEY `uq_cases_case_number` (`case_number`),
    KEY `idx_cases_status`      (`status`),
    KEY `idx_cases_managed_by`  (`managed_by`),
    -- RESTRICT: a report with an open case must not disappear.
    CONSTRAINT `fk_cases_report`      FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cases_managed_by`  FOREIGN KEY (`managed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cases_pet_profile` FOREIGN KEY (`pet_profile_id`)
        REFERENCES `pets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deferred FK completing the pets ↔ cases cycle.
--
-- Guarded by an information_schema lookup because ALTER TABLE ... ADD
-- CONSTRAINT has no IF NOT EXISTS in MySQL 8, so re-running this migration
-- would otherwise fail with "Duplicate foreign key constraint name".
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'pets'
      AND CONSTRAINT_NAME   = 'fk_pets_case'
);

SET @ddl := IF(
    @fk_exists = 0,
    'ALTER TABLE `pets` ADD CONSTRAINT `fk_pets_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE SET NULL',
    'DO 0'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Append-only progress log for a case (status changes and free-text notes).
CREATE TABLE IF NOT EXISTS `case_updates` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `case_id`     BIGINT UNSIGNED    NOT NULL,
    -- Both NULL when the entry is a note that did not change status.
    `from_status` VARCHAR(30)        NULL,
    `to_status`   VARCHAR(30)        NULL,
    `note`        TEXT               NULL,
    `created_by`  BIGINT UNSIGNED    NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cu_case_created` (`case_id`, `created_at`),
    CONSTRAINT `fk_cu_case`       FOREIGN KEY (`case_id`)
        REFERENCES `cases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cu_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
