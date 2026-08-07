-- =============================================================================
--  0008_reference_data
--
--  Reference data the application needs in order to function at all — as
--  opposed to db/seed.sql, which is demo content for development and grading
--  and must never be loaded into production.
--
--  Two things live here:
--    * verification_criteria — without at least one active criterion, officials
--      have no checklist and no report can be verified.
--    * resource_categories   — the educational library is grouped by category.
--
--  Barangays are deliberately NOT seeded here: they are specific to whichever
--  municipality is deploying. See the template at the bottom of this file.
--
--  Every statement uses INSERT IGNORE keyed on a unique column, so re-running
--  this migration is a no-op and it will not fight with db/seed.sql.
-- =============================================================================

SET NAMES utf8mb4;

-- ── Report verification checklist ────────────────────────────────────────────
-- created_by is NULL: these rows ship with the system rather than being
-- authored by a particular admin. Admins can edit, reorder or deactivate them
-- at /admin/system/criteria.php.
INSERT IGNORE INTO `verification_criteria`
    (`code`, `label`, `description`, `is_required`, `is_active`, `sort_order`, `created_by`)
VALUES
('has_photo',          'Report includes a clear photo of the animal',
 'At least one clear photo showing the animal is attached.',                                 1, 1, 1, NULL),
('valid_barangay',     'Location is within a recognized barangay',
 'The barangay selected is active and recognized in the system.',                            1, 1, 2, NULL),
('has_description',    'Description sufficiently describes the situation',
 'The description gives enough detail about the animal''s condition and behavior.',          1, 1, 3, NULL),
('not_duplicate',      'Not a duplicate of an existing open report',
 'A search was performed and no open report matches the same animal and location.',          1, 1, 4, NULL),
('location_specific',  'Address/landmark is specific enough to locate',
 'The address or landmark provided allows responders to find the animal.',                   0, 1, 5, NULL),
('urgency_reasonable', 'Selected urgency matches the situation',
 'The urgency level chosen is consistent with the described and photographed situation.',    0, 1, 6, NULL);

-- ── Educational resource categories ──────────────────────────────────────────
INSERT IGNORE INTO `resource_categories` (`name`, `slug`, `is_active`) VALUES
('Responsible Pet Ownership', 'responsible-pet-ownership', 1),
('Rabies Prevention',         'rabies-prevention',         1),
('Fostering & Adoption',      'fostering-adoption',        1),
('Community TNR',             'community-tnr',             1);

-- ── Barangays (deployment-specific — fill in before going live) ──────────────
--
-- Residents pick their barangay at registration and when filing a report, so a
-- production database needs the real list for the municipality being served.
-- Copy the template below into a new migration (0009_barangays_<municipality>)
-- with your own rows rather than editing this file:
--
--   INSERT IGNORE INTO `barangays` (`name`, `municipality`, `province`, `is_active`)
--   VALUES ('<barangay name>', '<municipality>', '<province>', 1);
--
-- db/seed.sql supplies five fictional barangays for local development.
