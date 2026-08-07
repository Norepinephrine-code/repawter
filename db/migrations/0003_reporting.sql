-- =============================================================================
--  0003_reporting
--  Adds: reports, report_checklist_responses, report_status_history
--
--  The core workflow of the system. A resident submits a report; an official
--  reviews it against the checklist; it is verified, assigned, worked and
--  resolved — or rejected as a duplicate. Reports are never hard-deleted, only
--  moved to status 'archived'.
--
--  Status flow:
--    submitted → under_review → verified → assigned → in_progress → resolved
--                     ↓
--                 rejected                         (any) → archived
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `reports` (
    `id`                  BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `reporter_id`         BIGINT UNSIGNED    NOT NULL,
    `barangay_id`         BIGINT UNSIGNED    NULL,
    `animal_type`         ENUM('dog','cat','other')
                                             NOT NULL DEFAULT 'dog',
    -- Free text used only when animal_type = 'other'.
    `species_other`       VARCHAR(80)        NULL,
    `title`               VARCHAR(150)       NULL,
    `description`         TEXT               NOT NULL,
    `location_address`    VARCHAR(255)       NOT NULL,
    `location_landmark`   VARCHAR(255)       NULL,
    -- Reserved for the optional Google Maps pin enhancement; the baseline
    -- implementation collects a text address only, so these stay NULL.
    `latitude`            DECIMAL(10,7)      NULL,
    `longitude`           DECIMAL(10,7)      NULL,
    `urgency`             ENUM('low','medium','high','critical')
                                             NOT NULL DEFAULT 'medium',
    -- Exactly one photo per report; required by the spec and by the checklist.
    `photo_path`          VARCHAR(255)       NOT NULL,
    `photo_original_name` VARCHAR(255)       NULL,
    `status`              ENUM('submitted','under_review','verified','rejected','assigned','in_progress','resolved','archived')
                                             NOT NULL DEFAULT 'submitted',
    -- Set when the report enters review; the reporter can no longer edit it.
    `is_locked`           TINYINT(1)         NOT NULL DEFAULT 0,
    `is_duplicate_of`     BIGINT UNSIGNED    NULL,
    `rejection_reason`    VARCHAR(500)       NULL,
    `assigned_to`         BIGINT UNSIGNED    NULL,
    `assigned_at`         DATETIME           NULL,
    `verified_by`         BIGINT UNSIGNED    NULL,
    `verified_at`         DATETIME           NULL,
    -- Together with created_at these drive the response-time analytics.
    `resolved_at`         DATETIME           NULL,
    `archived_at`         DATETIME           NULL,
    `created_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_reports_status`          (`status`),
    KEY `idx_reports_urgency`         (`urgency`),
    KEY `idx_reports_reporter_id`     (`reporter_id`),
    KEY `idx_reports_barangay_id`     (`barangay_id`),
    KEY `idx_reports_assigned_to`     (`assigned_to`),
    -- Composite index for the admin queue, which filters status then urgency.
    KEY `idx_reports_status_urgency`  (`status`, `urgency`),
    -- RESTRICT: a resident with reports on file cannot be deleted, preserving
    -- the reporting record. Suspend the account instead.
    CONSTRAINT `fk_reports_reporter`     FOREIGN KEY (`reporter_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reports_barangay`     FOREIGN KEY (`barangay_id`)
        REFERENCES `barangays` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_duplicate`    FOREIGN KEY (`is_duplicate_of`)
        REFERENCES `reports` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_assigned_to`  FOREIGN KEY (`assigned_to`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_verified_by`  FOREIGN KEY (`verified_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per (report, criterion): the reviewer's answer to the checklist.
CREATE TABLE IF NOT EXISTS `report_checklist_responses` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `report_id`   BIGINT UNSIGNED    NOT NULL,
    `criteria_id` BIGINT UNSIGNED    NOT NULL,
    `is_met`      TINYINT(1)         NOT NULL DEFAULT 0,
    `note`        VARCHAR(500)       NULL,
    `checked_by`  BIGINT UNSIGNED    NULL,
    `checked_at`  DATETIME           NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Enforces one answer per criterion and lets the app upsert on re-review.
    UNIQUE KEY `uq_rcr_report_criteria` (`report_id`, `criteria_id`),
    CONSTRAINT `fk_rcr_report`    FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE CASCADE,
    -- RESTRICT: keeps answered criteria readable; retire them with is_active = 0.
    CONSTRAINT `fk_rcr_criteria`  FOREIGN KEY (`criteria_id`)
        REFERENCES `verification_criteria` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rcr_checked_by` FOREIGN KEY (`checked_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only audit trail of every status transition. Never updated or deleted.
CREATE TABLE IF NOT EXISTS `report_status_history` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `report_id`   BIGINT UNSIGNED    NOT NULL,
    -- NULL on the very first row (creation).
    `from_status` VARCHAR(30)        NULL,
    `to_status`   VARCHAR(30)        NOT NULL,
    `changed_by`  BIGINT UNSIGNED    NULL,
    `note`        VARCHAR(500)       NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Serves the timeline query on the report detail page.
    KEY `idx_rsh_report_created` (`report_id`, `created_at`),
    CONSTRAINT `fk_rsh_report`     FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rsh_changed_by` FOREIGN KEY (`changed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
