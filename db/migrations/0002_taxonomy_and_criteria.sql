-- =============================================================================
--  0002_taxonomy_and_criteria
--  Adds: resource_categories, verification_criteria
--
--  Two admin-editable lookup tables. Both are referenced by later tables, so
--  they are created before the reporting and content tables.
--
--  `verification_criteria` is the admin-defined checklist a report must satisfy
--  before an official may mark it "verified" — the criteria are data, not code,
--  so a system admin can change them at /admin/system/criteria.php without a
--  deployment.
-- =============================================================================

SET NAMES utf8mb4;

-- Groups educational articles (rabies prevention, responsible ownership, ...).
CREATE TABLE IF NOT EXISTS `resource_categories` (
    `id`         BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100)       NOT NULL,
    -- URL-safe form of `name`, used in public links.
    `slug`       VARCHAR(120)       NOT NULL,
    `is_active`  TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at` DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rc_name` (`name`),
    UNIQUE KEY `uq_rc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The report verification checklist.
--
-- Rows are deactivated (is_active = 0) rather than deleted, because
-- report_checklist_responses references them with ON DELETE RESTRICT — an
-- answered checklist must stay readable for audit even after the criterion is
-- retired.
CREATE TABLE IF NOT EXISTS `verification_criteria` (
    `id`          BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- Stable machine name (e.g. 'has_photo'); the label may be reworded freely.
    `code`        VARCHAR(50)        NOT NULL,
    `label`       VARCHAR(200)       NOT NULL,
    `description` VARCHAR(500)       NULL,
    -- Required criteria must all be met before a report can be verified.
    `is_required` TINYINT(1)         NOT NULL DEFAULT 1,
    `is_active`   TINYINT(1)         NOT NULL DEFAULT 1,
    `sort_order`  INT                NOT NULL DEFAULT 0,
    `created_by`  BIGINT UNSIGNED    NULL,
    `created_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_vc_code` (`code`),
    -- Serves the "active criteria in display order" query on every review page.
    KEY `idx_vc_active_sort` (`is_active`, `sort_order`),
    CONSTRAINT `fk_vc_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
