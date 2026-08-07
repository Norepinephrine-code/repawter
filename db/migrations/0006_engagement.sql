-- =============================================================================
--  0006_engagement
--  Adds: announcements, notifications, email_outbox, educational_resources
--
--  Everything the platform uses to communicate outward: the shared calendar,
--  in-app notifications, the logged "email" outbox, and the educational
--  library.
-- =============================================================================

SET NAMES utf8mb4;

-- Calendar entries: vaccination drives, adoption events, TNR schedules.
-- Read-only for residents — there is no commenting or posting.
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `author_id`      BIGINT UNSIGNED    NOT NULL,
    -- NULL means the announcement is platform-wide rather than barangay-scoped.
    `barangay_id`    BIGINT UNSIGNED    NULL,
    -- Drives the colour/legend on the FullCalendar view.
    `category`       ENUM('vaccination_drive','adoption_event','tnr_schedule','welfare_operation','general')
                                        NOT NULL DEFAULT 'general',
    `title`          VARCHAR(200)       NOT NULL,
    `body`           TEXT               NOT NULL,
    `location_text`  VARCHAR(255)       NULL,
    -- NULL for a notice with no date; set for anything shown on the calendar.
    `event_start`    DATETIME           NULL,
    `event_end`      DATETIME           NULL,
    `is_all_day`     TINYINT(1)         NOT NULL DEFAULT 0,
    -- Optional link to a related Facebook post. Link only — nothing is
    -- cross-posted to social media by the platform.
    `facebook_url`   VARCHAR(500)       NULL,
    `status`         ENUM('draft','scheduled','published','archived')
                                        NOT NULL DEFAULT 'draft',
    -- publish_at is the intended time; published_at is when it actually went live.
    `publish_at`     DATETIME           NULL,
    `published_at`   DATETIME           NULL,
    `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ann_status`        (`status`),
    KEY `idx_ann_category`      (`category`),
    KEY `idx_ann_event_start`   (`event_start`),
    -- Serves the calendar feed: published events within a date window.
    KEY `idx_ann_status_event`  (`status`, `event_start`),
    CONSTRAINT `fk_ann_author`   FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ann_barangay` FOREIGN KEY (`barangay_id`)
        REFERENCES `barangays` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- In-app notifications. Append-only apart from `read_at`.
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`      BIGINT UNSIGNED    NOT NULL,
    `type`         VARCHAR(50)        NOT NULL DEFAULT 'system',
    `title`        VARCHAR(200)       NOT NULL,
    `body`         VARCHAR(500)       NULL,
    -- Loose polymorphic pointer ('report', 'foster_application', ...). Not a FK,
    -- because the target table varies; link_url is what the UI actually follows.
    `related_type` VARCHAR(30)        NULL,
    `related_id`   BIGINT UNSIGNED    NULL,
    `link_url`     VARCHAR(255)       NULL,
    -- NULL = unread. Powers the navbar unread badge.
    `read_at`      DATETIME           NULL,
    `created_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notif_user_read`  (`user_id`, `read_at`),
    KEY `idx_notif_created_at` (`created_at`),
    -- CASCADE: notifications are private to one user and worthless without them.
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log of emails the system would have sent.
--
-- Real SMTP delivery is out of scope, so notify_email() writes here instead.
-- Wiring in a mailer later means reading 'queued' rows and marking them 'sent',
-- with no schema change required.
CREATE TABLE IF NOT EXISTS `email_outbox` (
    `id`            BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- NULL if the recipient has no account (or was later deleted).
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

-- Public articles on responsible pet ownership and rabies prevention.
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
    -- RESTRICT: a category with articles in it cannot be deleted out from under them.
    CONSTRAINT `fk_er_category` FOREIGN KEY (`category_id`)
        REFERENCES `resource_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_er_author`   FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
