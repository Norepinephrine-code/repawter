-- =============================================================================
--  0005_applications
--  Adds: foster_applications, adoption_applications, adoption_agreements
--
--  Both application types are reviewed manually — the system never auto-matches
--  an applicant to an animal. Approving an adoption unlocks generation of a PDF
--  agreement, which is recorded once in `adoption_agreements`.
-- =============================================================================

SET NAMES utf8mb4;

-- A resident's standing offer to foster. Not tied to a specific animal:
-- capacity and preferences are declared up front and matched by hand.
CREATE TABLE IF NOT EXISTS `foster_applications` (
    `id`                    BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `applicant_id`          BIGINT UNSIGNED    NOT NULL,
    `status`                ENUM('submitted','under_review','approved','rejected','withdrawn')
                                               NOT NULL DEFAULT 'submitted',
    `preferred_animal_type` ENUM('dog','cat','other')
                                               NOT NULL DEFAULT 'dog',
    `preferred_notes`       VARCHAR(500)       NULL,
    -- Approvals are capped at the declared household capacity.
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
    -- RESTRICT keeps applicant history intact for the reviewer.
    CONSTRAINT `fk_fa_applicant`   FOREIGN KEY (`applicant_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_fa_reviewed_by` FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- An application to permanently adopt one specific pet.
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
    -- Set when the handover actually happened, which is after approval.
    `completed_at`    DATETIME           NULL,
    `created_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- One application per person per pet; blocks accidental double submission.
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

-- Record of a generated PDF adoption agreement (see lib/fpdf).
--
-- pet_id and adopter_id are denormalised from the application on purpose: the
-- agreement is a legal-ish document and must keep naming the parties it was
-- generated for, even if the application is later edited.
CREATE TABLE IF NOT EXISTS `adoption_agreements` (
    `id`                       BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `adoption_application_id`  BIGINT UNSIGNED    NOT NULL,
    `pet_id`                   BIGINT UNSIGNED    NOT NULL,
    `adopter_id`               BIGINT UNSIGNED    NOT NULL,
    `agreement_number`         VARCHAR(30)        NOT NULL,
    -- Path relative to UPLOAD_DIR, never a full filesystem path.
    `pdf_path`                 VARCHAR(255)       NOT NULL,
    `generated_by`             BIGINT UNSIGNED    NULL,
    `generated_at`             DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`               DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Exactly one agreement per application; regenerating reuses the row.
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
