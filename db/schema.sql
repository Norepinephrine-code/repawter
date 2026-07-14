-- ============================================================
--  RePawter — schema.sql
--  MySQL / MariaDB  |  ENGINE=InnoDB  |  utf8mb4_unicode_ci
--  Creation order chosen to satisfy foreign key dependencies.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ── 1. barangays ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `barangays` (
    `id`           BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(120)       NOT NULL,
    `municipality` VARCHAR(120)       NULL,
    `province`     VARCHAR(120)       NULL,
    `is_active`    TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_barangay_name_muni` (`name`, `municipality`),
    KEY `idx_barangay_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. users ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`                BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `role`              ENUM('community_resident','barangay_official','welfare_org','system_admin')
                                           NOT NULL DEFAULT 'community_resident',
    `account_status`    ENUM('active','flagged','suspended')
                                           NOT NULL DEFAULT 'active',
    `email`             VARCHAR(255)       NOT NULL,
    `password_hash`     VARCHAR(255)       NOT NULL,
    `full_name`         VARCHAR(150)       NOT NULL,
    `contact_number`    VARCHAR(30)        NULL,
    `barangay_id`       BIGINT UNSIGNED    NULL,
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
    CONSTRAINT `fk_users_barangay` FOREIGN KEY (`barangay_id`)
        REFERENCES `barangays` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. resource_categories ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `resource_categories` (
    `id`         BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100)       NOT NULL,
    `slug`       VARCHAR(120)       NOT NULL,
    `is_active`  TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at` DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rc_name` (`name`),
    UNIQUE KEY `uq_rc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. verification_criteria ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `verification_criteria` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `code`        VARCHAR(50)        NOT NULL,
    `label`       VARCHAR(200)       NOT NULL,
    `description` VARCHAR(500)       NULL,
    `is_required` TINYINT(1)         NOT NULL DEFAULT 1,
    `is_active`   TINYINT(1)         NOT NULL DEFAULT 1,
    `sort_order`  INT                NOT NULL DEFAULT 0,
    `created_by`  BIGINT UNSIGNED    NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_vc_code` (`code`),
    KEY `idx_vc_active_sort` (`is_active`, `sort_order`),
    CONSTRAINT `fk_vc_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. reports ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reports` (
    `id`                  BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `reporter_id`         BIGINT UNSIGNED    NOT NULL,
    `barangay_id`         BIGINT UNSIGNED    NULL,
    `animal_type`         ENUM('dog','cat','other')
                                             NOT NULL DEFAULT 'dog',
    `species_other`       VARCHAR(80)        NULL,
    `title`               VARCHAR(150)       NULL,
    `description`         TEXT               NOT NULL,
    `location_address`    VARCHAR(255)       NOT NULL,
    `location_landmark`   VARCHAR(255)       NULL,
    `latitude`            DECIMAL(10,7)      NULL,
    `longitude`           DECIMAL(10,7)      NULL,
    `urgency`             ENUM('low','medium','high','critical')
                                             NOT NULL DEFAULT 'medium',
    `photo_path`          VARCHAR(255)       NOT NULL,
    `photo_original_name` VARCHAR(255)       NULL,
    `status`              ENUM('submitted','under_review','verified','rejected','assigned','in_progress','resolved','archived')
                                             NOT NULL DEFAULT 'submitted',
    `is_locked`           TINYINT(1)         NOT NULL DEFAULT 0,
    `is_duplicate_of`     BIGINT UNSIGNED    NULL,
    `rejection_reason`    VARCHAR(500)       NULL,
    `assigned_to`         BIGINT UNSIGNED    NULL,
    `assigned_at`         DATETIME           NULL,
    `verified_by`         BIGINT UNSIGNED    NULL,
    `verified_at`         DATETIME           NULL,
    `resolved_at`         DATETIME           NULL,
    `archived_at`         DATETIME           NULL,
    `created_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_reports_status`          (`status`),
    KEY `idx_reports_urgency`         (`urgency`),
    KEY `idx_reports_reporter_id`     (`reporter_id`),
    KEY `idx_reports_barangay_id`     (`barangay_id`),
    KEY `idx_reports_assigned_to`     (`assigned_to`),
    KEY `idx_reports_status_urgency`  (`status`, `urgency`),
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

-- ── 6. report_checklist_responses ────────────────────────────
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
    UNIQUE KEY `uq_rcr_report_criteria` (`report_id`, `criteria_id`),
    CONSTRAINT `fk_rcr_report`    FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rcr_criteria`  FOREIGN KEY (`criteria_id`)
        REFERENCES `verification_criteria` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rcr_checked_by` FOREIGN KEY (`checked_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. report_status_history (append-only) ───────────────────
CREATE TABLE IF NOT EXISTS `report_status_history` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `report_id`   BIGINT UNSIGNED    NOT NULL,
    `from_status` VARCHAR(30)        NULL,
    `to_status`   VARCHAR(30)        NOT NULL,
    `changed_by`  BIGINT UNSIGNED    NULL,
    `note`        VARCHAR(500)       NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rsh_report_created` (`report_id`, `created_at`),
    CONSTRAINT `fk_rsh_report`     FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rsh_changed_by` FOREIGN KEY (`changed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. pets ──────────────────────────────────────────────────
--  (case_id FK added after cases table via ALTER)
CREATE TABLE IF NOT EXISTS `pets` (
    `id`               BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `case_id`          BIGINT UNSIGNED    NULL,
    `created_by`       BIGINT UNSIGNED    NOT NULL,
    `name`             VARCHAR(80)        NOT NULL,
    `animal_type`      ENUM('dog','cat','other')
                                          NOT NULL DEFAULT 'dog',
    `species_other`    VARCHAR(80)        NULL,
    `breed`            VARCHAR(80)        NULL,
    `sex`              ENUM('male','female','unknown')
                                          NOT NULL DEFAULT 'unknown',
    `approx_age_months` INT UNSIGNED      NULL,
    `size`             ENUM('small','medium','large')
                                          NULL,
    `color`            VARCHAR(80)        NULL,
    `is_vaccinated`    TINYINT(1)         NULL,
    `is_neutered`      TINYINT(1)         NULL,
    `description`      TEXT               NULL,
    `photo_path`       VARCHAR(255)       NULL,
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

-- ── 9. cases ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cases` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `report_id`      BIGINT UNSIGNED    NOT NULL,
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
    UNIQUE KEY `uq_cases_report_id`   (`report_id`),
    UNIQUE KEY `uq_cases_case_number` (`case_number`),
    KEY `idx_cases_status`      (`status`),
    KEY `idx_cases_managed_by`  (`managed_by`),
    CONSTRAINT `fk_cases_report`      FOREIGN KEY (`report_id`)
        REFERENCES `reports` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cases_managed_by`  FOREIGN KEY (`managed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cases_pet_profile` FOREIGN KEY (`pet_profile_id`)
        REFERENCES `pets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add deferred FK from pets.case_id → cases.id
ALTER TABLE `pets`
    ADD CONSTRAINT `fk_pets_case`
        FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE SET NULL;

-- ── 10. case_updates (append-only) ───────────────────────────
CREATE TABLE IF NOT EXISTS `case_updates` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `case_id`     BIGINT UNSIGNED    NOT NULL,
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

-- ── 11. foster_applications ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `foster_applications` (
    `id`                    BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `applicant_id`          BIGINT UNSIGNED    NOT NULL,
    `status`                ENUM('submitted','under_review','approved','rejected','withdrawn')
                                               NOT NULL DEFAULT 'submitted',
    `preferred_animal_type` ENUM('dog','cat','other')
                                               NOT NULL DEFAULT 'dog',
    `preferred_notes`       VARCHAR(500)       NULL,
    `household_capacity`    INT UNSIGNED       NOT NULL DEFAULT 1,
    `current_foster_count`  INT UNSIGNED       NOT NULL DEFAULT 0,
    `has_yard`              TINYINT(1)         NULL,
    `household_notes`       VARCHAR(500)       NULL,
    `reviewed_by`           BIGINT UNSIGNED    NULL,
    `reviewed_at`           DATETIME           NULL,
    `decision_notes`        VARCHAR(500)       NULL,
    `created_at`            DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_fa_status`       (`status`),
    KEY `idx_fa_applicant_id` (`applicant_id`),
    CONSTRAINT `fk_fa_applicant`   FOREIGN KEY (`applicant_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_fa_reviewed_by` FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. adoption_applications ────────────────────────────────
CREATE TABLE IF NOT EXISTS `adoption_applications` (
    `id`              BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pet_id`          BIGINT UNSIGNED    NOT NULL,
    `applicant_id`    BIGINT UNSIGNED    NOT NULL,
    `status`          ENUM('submitted','under_review','approved','rejected','withdrawn','completed')
                                         NOT NULL DEFAULT 'submitted',
    `message`         TEXT               NULL,
    `home_type`       VARCHAR(120)       NULL,
    `has_other_pets`  TINYINT(1)         NULL,
    `reviewed_by`     BIGINT UNSIGNED    NULL,
    `reviewed_at`     DATETIME           NULL,
    `decision_notes`  VARCHAR(500)       NULL,
    `completed_at`    DATETIME           NULL,
    `created_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aa_pet_applicant` (`pet_id`, `applicant_id`),
    KEY `idx_aa_status`       (`status`),
    KEY `idx_aa_pet_id`       (`pet_id`),
    KEY `idx_aa_applicant_id` (`applicant_id`),
    CONSTRAINT `fk_aa_pet`         FOREIGN KEY (`pet_id`)
        REFERENCES `pets` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_aa_applicant`   FOREIGN KEY (`applicant_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_aa_reviewed_by` FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 13. adoption_agreements ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `adoption_agreements` (
    `id`                       BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `adoption_application_id`  BIGINT UNSIGNED    NOT NULL,
    `pet_id`                   BIGINT UNSIGNED    NOT NULL,
    `adopter_id`               BIGINT UNSIGNED    NOT NULL,
    `agreement_number`         VARCHAR(30)        NOT NULL,
    `pdf_path`                 VARCHAR(255)       NOT NULL,
    `generated_by`             BIGINT UNSIGNED    NULL,
    `generated_at`             DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`               DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_agreement_app_id` (`adoption_application_id`),
    UNIQUE KEY `uq_agreement_number` (`agreement_number`),
    CONSTRAINT `fk_agr_application` FOREIGN KEY (`adoption_application_id`)
        REFERENCES `adoption_applications` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_agr_pet`         FOREIGN KEY (`pet_id`)
        REFERENCES `pets` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_agr_adopter`     FOREIGN KEY (`adopter_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_agr_generated_by` FOREIGN KEY (`generated_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 14. announcements ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `author_id`      BIGINT UNSIGNED    NOT NULL,
    `barangay_id`    BIGINT UNSIGNED    NULL,
    `category`       ENUM('vaccination_drive','adoption_event','tnr_schedule','welfare_operation','general')
                                        NOT NULL DEFAULT 'general',
    `title`          VARCHAR(200)       NOT NULL,
    `body`           TEXT               NOT NULL,
    `location_text`  VARCHAR(255)       NULL,
    `event_start`    DATETIME           NULL,
    `event_end`      DATETIME           NULL,
    `is_all_day`     TINYINT(1)         NOT NULL DEFAULT 0,
    `facebook_url`   VARCHAR(500)       NULL,
    `status`         ENUM('draft','scheduled','published','archived')
                                        NOT NULL DEFAULT 'draft',
    `publish_at`     DATETIME           NULL,
    `published_at`   DATETIME           NULL,
    `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ann_status`        (`status`),
    KEY `idx_ann_category`      (`category`),
    KEY `idx_ann_event_start`   (`event_start`),
    KEY `idx_ann_status_event`  (`status`, `event_start`),
    CONSTRAINT `fk_ann_author`   FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ann_barangay` FOREIGN KEY (`barangay_id`)
        REFERENCES `barangays` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 15. notifications (append-only + read_at) ────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`      BIGINT UNSIGNED    NOT NULL,
    `type`         VARCHAR(50)        NOT NULL DEFAULT 'system',
    `title`        VARCHAR(200)       NOT NULL,
    `body`         VARCHAR(500)       NULL,
    `related_type` VARCHAR(30)        NULL,
    `related_id`   BIGINT UNSIGNED    NULL,
    `link_url`     VARCHAR(255)       NULL,
    `read_at`      DATETIME           NULL,
    `created_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notif_user_read`  (`user_id`, `read_at`),
    KEY `idx_notif_created_at` (`created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 16. email_outbox (append-only) ───────────────────────────
CREATE TABLE IF NOT EXISTS `email_outbox` (
    `id`            BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED    NULL,
    `to_email`      VARCHAR(255)       NOT NULL,
    `subject`       VARCHAR(255)       NOT NULL,
    `body`          TEXT               NOT NULL,
    `type`          VARCHAR(50)        NOT NULL DEFAULT 'system',
    `related_type`  VARCHAR(30)        NULL,
    `related_id`    BIGINT UNSIGNED    NULL,
    `status`        ENUM('queued','sent','failed')
                                       NOT NULL DEFAULT 'sent',
    `error_message` VARCHAR(255)       NULL,
    `queued_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at`       DATETIME           NULL,
    `created_at`    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_eo_status`     (`status`),
    KEY `idx_eo_user_id`    (`user_id`),
    KEY `idx_eo_created_at` (`created_at`),
    CONSTRAINT `fk_eo_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 17. educational_resources ────────────────────────────────
CREATE TABLE IF NOT EXISTS `educational_resources` (
    `id`               BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_id`      BIGINT UNSIGNED    NOT NULL,
    `author_id`        BIGINT UNSIGNED    NULL,
    `title`            VARCHAR(200)       NOT NULL,
    `slug`             VARCHAR(220)       NOT NULL,
    `body`             MEDIUMTEXT         NOT NULL,
    `summary`          VARCHAR(500)       NULL,
    `cover_image_path` VARCHAR(255)       NULL,
    `is_published`     TINYINT(1)         NOT NULL DEFAULT 1,
    `published_at`     DATETIME           NULL,
    `created_at`       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_er_slug` (`slug`),
    KEY `idx_er_category_id`      (`category_id`),
    KEY `idx_er_published`        (`is_published`, `published_at`),
    CONSTRAINT `fk_er_category` FOREIGN KEY (`category_id`)
        REFERENCES `resource_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_er_author`   FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 18. audit_logs (append-only) ─────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `actor_id`       BIGINT UNSIGNED    NULL,
    `target_user_id` BIGINT UNSIGNED    NULL,
    `action`         VARCHAR(50)        NOT NULL,
    `entity_type`    VARCHAR(50)        NULL,
    `entity_id`      BIGINT UNSIGNED    NULL,
    `description`    VARCHAR(500)       NULL,
    `metadata_json`  TEXT               NULL,
    `ip_address`     VARCHAR(45)        NULL,
    `user_agent`     VARCHAR(255)       NULL,
    `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_al_actor_id`       (`actor_id`),
    KEY `idx_al_target_user_id` (`target_user_id`),
    KEY `idx_al_action`         (`action`),
    KEY `idx_al_created_at`     (`created_at`),
    CONSTRAINT `fk_al_actor`       FOREIGN KEY (`actor_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_al_target_user` FOREIGN KEY (`target_user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
