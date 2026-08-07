-- =============================================================================
--  0007_audit
--  Adds: audit_logs
--
--  Append-only record of privileged actions — user management, criteria edits,
--  report archival — surfaced to system admins at /admin/system/audit_logs.php.
--
--  Both actor and target use ON DELETE SET NULL rather than CASCADE: deleting a
--  user must never erase the evidence of what was done to, or by, that account.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- Who performed the action. NULL for system-initiated events.
    `actor_id`       BIGINT UNSIGNED    NULL,
    -- Set only for user-management actions (flag, suspend, reinstate).
    `target_user_id` BIGINT UNSIGNED    NULL,
    `action`         VARCHAR(50)        NOT NULL,
    -- Loose polymorphic pointer to the affected record ('report', 'pet', ...).
    `entity_type`    VARCHAR(50)        NULL,
    `entity_id`      BIGINT UNSIGNED    NULL,
    `description`    VARCHAR(500)       NULL,
    -- Free-form JSON payload for anything not worth a dedicated column.
    `metadata_json`  TEXT               NULL,
    -- 45 chars fits an IPv6 address, including IPv4-mapped form.
    `ip_address`     VARCHAR(45)        NULL,
    `user_agent`     VARCHAR(255)       NULL,
    `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_al_actor_id`       (`actor_id`),
    KEY `idx_al_target_user_id` (`target_user_id`),
    KEY `idx_al_action`         (`action`),
    -- The audit log page reads newest-first.
    KEY `idx_al_created_at`     (`created_at`),
    CONSTRAINT `fk_al_actor`       FOREIGN KEY (`actor_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_al_target_user` FOREIGN KEY (`target_user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
