-- ============================================================
--  RePawter — seed.sql
--  Demo data for development / grading.
--  All demo users share password: Password123!
--  Hash: $2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ── barangays (ids 1-5) ──────────────────────────────────────
INSERT INTO `barangays` (`id`, `name`, `municipality`, `province`, `is_active`) VALUES
(1, 'San Isidro',    'Sampleton', 'Metro Demo', 1),
(2, 'Poblacion',     'Sampleton', 'Metro Demo', 1),
(3, 'Santa Cruz',    'Sampleton', 'Metro Demo', 1),
(4, 'Bagong Silang', 'Sampleton', 'Metro Demo', 1),
(5, 'Malapit',       'Sampleton', 'Metro Demo', 1);

-- ── users (ids 1-8) ──────────────────────────────────────────
-- All passwords = Password123!
INSERT INTO `users`
    (`id`, `role`, `account_status`, `email`, `password_hash`, `full_name`,
     `contact_number`, `barangay_id`, `organization_name`, `flagged_reason`, `created_by`, `created_at`)
VALUES
(1, 'system_admin',       'active',  'admin@repawter.test',          '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'System Admin',     NULL,            NULL, NULL,             NULL, NULL, '2026-01-01 08:00:00'),
(2, 'barangay_official',  'active',  'official.brgy1@repawter.test', '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Ana Reyes',        NULL,            1,    NULL,             NULL, 1,    '2026-01-05 09:00:00'),
(3, 'barangay_official',  'active',  'official.brgy2@repawter.test', '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Ben Cruz',         NULL,            2,    NULL,             NULL, 1,    '2026-01-05 09:05:00'),
(4, 'welfare_org',        'active',  'shelter@pawrescue.test',       '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Paw Rescue PH',    NULL,            NULL, 'Paw Rescue PH',  NULL, 1,    '2026-01-10 10:00:00'),
(5, 'welfare_org',        'active',  'org@strayhaven.test',          '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Stray Haven',      NULL,            NULL, 'Stray Haven',    NULL, 1,    '2026-01-10 10:10:00'),
(6, 'community_resident', 'active',  'juan.delacruz@example.test',   '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Juan Dela Cruz',   '09171234567',   1,    NULL,             NULL, NULL, '2026-02-01 11:00:00'),
(7, 'community_resident', 'active',  'maria.santos@example.test',    '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Maria Santos',     '09179876543',   2,    NULL,             NULL, NULL, '2026-02-03 13:00:00'),
(8, 'community_resident', 'flagged', 'pedro.reyes@example.test',     '$2y$10$JzqSU3HbWLX5qAleWe3jZOwPL/QS5kdEiPMEhbrMX3oUZ/KoJ9eiO', 'Pedro Reyes',      NULL,            3,    NULL, 'Multiple duplicate reports', NULL, '2026-02-05 14:00:00');

-- ── resource_categories ──────────────────────────────────────
INSERT INTO `resource_categories` (`id`, `name`, `slug`, `is_active`) VALUES
(1, 'Responsible Pet Ownership', 'responsible-pet-ownership', 1),
(2, 'Rabies Prevention',          'rabies-prevention',         1),
(3, 'Fostering & Adoption',       'fostering-adoption',        1),
(4, 'Community TNR',              'community-tnr',             1);

-- ── verification_criteria ────────────────────────────────────
INSERT INTO `verification_criteria`
    (`id`, `code`, `label`, `description`, `is_required`, `is_active`, `sort_order`, `created_by`)
VALUES
(1, 'has_photo',       'Report includes a clear photo of the animal',            'At least one clear photo showing the animal is attached.',                       1, 1, 1, 1),
(2, 'valid_barangay',  'Location is within a recognized barangay',               'The barangay selected is active and recognized in the system.',                   1, 1, 2, 1),
(3, 'has_description', 'Description sufficiently describes the situation',       'The description gives enough detail about the animal\'s condition and behavior.', 1, 1, 3, 1),
(4, 'not_duplicate',   'Not a duplicate of an existing open report',             'A search was performed and no open report matches the same animal and location.', 1, 1, 4, 1),
(5, 'location_specific','Address/landmark is specific enough to locate',         'The address or landmark provided allows responders to find the animal.',          0, 1, 5, 1),
(6, 'urgency_reasonable','Selected urgency matches the situation',               'The urgency level chosen is consistent with the described and photographed situation.', 0, 1, 6, 1);

-- ── educational_resources ────────────────────────────────────
INSERT INTO `educational_resources`
    (`id`, `category_id`, `author_id`, `title`, `slug`, `body`, `summary`, `is_published`, `published_at`)
VALUES
(1, 2, 1,
 'Rabies 101: Signs, Prevention, and What to Do After a Bite',
 'rabies-101-signs-prevention-what-to-do-after-a-bite',
 '<h2>What Is Rabies?</h2>
<p>Rabies is a deadly viral disease caused by the <em>Lyssavirus</em> that affects the central nervous system of all mammals, including dogs, cats, and humans. It is transmitted primarily through the saliva of an infected animal, usually via a bite wound. In the Philippines, dogs account for more than 95% of human rabies cases.</p>
<p>Once clinical symptoms appear in a human, rabies is almost always fatal. This is why prevention — through responsible pet ownership, vaccination, and prompt post-exposure treatment — is critically important. Fortunately, rabies is entirely preventable when proper precautions are taken.</p>
<h2>Warning Signs in Animals</h2>
<p>An animal infected with rabies may show drastic behavioral changes. A normally friendly dog may become aggressive or restless; a wild animal may lose its fear of humans. Other signs include excessive drooling, difficulty swallowing, unprovoked aggression, disorientation, and paralysis. If you observe these signs in any animal, maintain your distance and report it immediately using RePawter.</p>
<h2>What to Do After a Bite</h2>
<p>If you are bitten or scratched by a potentially rabid animal, act immediately: (1) Wash the wound thoroughly with soap and running water for at least 15 minutes. (2) Apply an antiseptic such as povidone-iodine. (3) Seek medical attention at the nearest Animal Bite Treatment Center (ABTC) as soon as possible for post-exposure prophylaxis (PEP). (4) Report the biting animal to your barangay officials so it can be observed or tested. Do not wait for symptoms — by then it is too late.</p>',
 'Learn the warning signs of rabies in animals, how to protect your pet through vaccination, and the critical steps to take immediately after a bite.',
 1, '2026-01-15 08:00:00'),

(2, 3, 1,
 'So You Want to Foster: A Beginner\'s Guide',
 'so-you-want-to-foster-a-beginners-guide',
 '<h2>Why Fostering Matters</h2>
<p>Fostering an animal means providing a safe, loving temporary home while a rescue organization or shelter works on finding the animal a permanent family. Fosters save lives — every animal in a foster home frees up space in shelters, reduces stress on animals, and gives rescuers critical insight into the animal\'s personality and needs.</p>
<p>Fostered animals tend to be more relaxed and better socialized than shelter animals, which makes them easier to adopt out. In many cases, fosters themselves fall in love and become permanent adopters — what rescue organizations lovingly call a "foster fail" (which is really a win!).</p>
<h2>What Fostering Requires</h2>
<p>You don\'t need to be an expert animal handler to be a great foster. You do need: a safe indoor space free from hazards, commitment to feeding, socializing, and monitoring the animal daily, willingness to attend vet visits as directed by your rescue coordinator, and the emotional resilience to say goodbye when a permanent home is found. Most organizations supply food, basic supplies, and cover vet costs — you supply the love and space.</p>
<h2>How to Apply Through RePawter</h2>
<p>To become a foster through our network, click "Become a Foster" in the navigation menu and complete a short application. A welfare organization coordinator will review your application, potentially conduct a home check, and match you with an animal that suits your household. Applications typically take 3–7 days to process.</p>',
 'A practical introduction to fostering animals, what it takes, what to expect, and how to get started through the RePawter network.',
 1, '2026-01-20 08:00:00'),

(3, 1, 1,
 'Responsible Pet Ownership Basics',
 'responsible-pet-ownership-basics',
 '<h2>The Commitment Behind the Cuteness</h2>
<p>Owning a pet is one of the most rewarding experiences life offers — but it comes with serious, long-term responsibilities. A dog or cat can live 10–20 years, and every day of that life depends on you for food, shelter, health care, socialization, and safety. Before adopting or buying a pet, honestly assess your lifestyle, budget, and living situation to ensure you can provide all these needs throughout the animal\'s life.</p>
<p>Responsible pet ownership begins with registration and vaccination. In the Philippines, all dogs must be registered with the local government unit (LGU) and vaccinated against rabies annually under Republic Act 9482, the Anti-Rabies Act of 2007. Keeping vaccination records current protects your pet, your family, and your community.</p>
<h2>Everyday Care Essentials</h2>
<p>Provide fresh water and appropriate, balanced nutrition daily. Exercise your dog regularly — at minimum, two walks per day for most breeds. Keep cats stimulated indoors with toys and scratching posts. Schedule annual veterinary check-ups even when your pet appears healthy, as many conditions are easier and cheaper to treat when caught early. Groom your pet regularly and keep their living area clean.</p>
<h2>Being a Good Neighbor</h2>
<p>Responsible pet owners keep their animals from becoming nuisances or dangers. Always leash your dog in public. Clean up after your pet. Never let your cat roam at night unsupervised (this is the leading cause of cat injuries and the spread of disease). If your pet barks excessively or disturbs neighbors, address the root cause — usually boredom or anxiety — rather than ignoring it. Spaying or neutering your pet prevents accidental litters that contribute to the stray animal population.</p>',
 'A comprehensive overview of what responsible pet ownership means: registration, vaccination, daily care, and community responsibilities.',
 1, '2026-01-25 08:00:00'),

(4, 4, 1,
 'Understanding Trap-Neuter-Return (TNR) for Community Cats',
 'understanding-trap-neuter-return-tnr-community-cats',
 '<h2>What Is TNR?</h2>
<p>Trap-Neuter-Return (TNR) is a humane, evidence-based method of managing feral and community cat populations. Cats are humanely trapped, sterilized and vaccinated by a veterinarian, then returned to their outdoor home where they are cared for by community volunteers called "cat colony managers." TNR has been shown to stabilize and gradually reduce feral cat populations over time — unlike lethal removal methods, which create a "vacuum effect" where new cats move in to fill the void.</p>
<p>TNR-managed cats are ear-tipped (the tip of one ear is surgically removed while the cat is under anesthesia) as a universal, lifelong indicator that the cat has been sterilized and vaccinated. This helps animal control officers and community members quickly identify managed colony cats versus unmanaged strays.</p>
<h2>Benefits to the Community</h2>
<p>Beyond population control, TNR provides multiple community benefits. Vaccinated colony cats are no longer a rabies vector. Sterilized cats exhibit fewer nuisance behaviors such as yowling, fighting, and spraying. Community members who feed and monitor colonies become invested stakeholders in animal welfare, creating stronger social cohesion. TNR is also significantly more cost-effective than repeated capture-and-kill programs, which must be maintained indefinitely to prevent population rebound.</p>
<h2>How You Can Help</h2>
<p>If you\'ve noticed a community cat colony in your barangay, you can help by contacting a registered welfare organization through RePawter to coordinate a TNR drive. Volunteers are needed for trapping, transport, post-operative monitoring, and feeding. Barangay officials can support TNR programs by allocating resources and communicating with residents. Together, we can make our community safer and more humane for both people and animals.</p>',
 'Explains what TNR is, how it works, why it is more effective than lethal removal, and how community members can participate.',
 1, '2026-02-01 08:00:00');

-- ── announcements ────────────────────────────────────────────
INSERT INTO `announcements`
    (`id`, `author_id`, `barangay_id`, `category`, `title`, `body`, `location_text`,
     `event_start`, `event_end`, `is_all_day`, `facebook_url`, `status`, `publish_at`, `published_at`)
VALUES
(1, 2, 1, 'vaccination_drive',
 'Free Rabies Vaccination Drive — San Isidro Barangay',
 '<p>All residents of San Isidro are invited to bring their dogs and cats for a <strong>FREE anti-rabies vaccination</strong> on July 20, 2026. This drive is part of the LGU\'s annual Responsible Pet Ownership Month observance. No registration needed — just bring your pet on a leash or in a secure carrier. Veterinary volunteers from Paw Rescue PH will be on-site.</p>',
 'San Isidro Barangay Hall Covered Court',
 '2026-07-20 09:00:00', '2026-07-20 15:00:00', 0,
 'https://www.facebook.com/',
 'published', NULL, '2026-07-10 08:00:00'),

(2, 4, NULL, 'adoption_event',
 'Mega Adoption Fair — Find Your Forever Pet!',
 '<p>Paw Rescue PH is hosting its biggest adoption fair of the year! Come meet <strong>over 30 animals</strong> looking for their forever homes — dogs, cats, puppies, and kittens. Adoption fees are waived for the day. Each adopted pet comes with a starter kit (food, collar, and health card).</p><p>Bring a valid ID and proof of residence. Children under 18 must be accompanied by a parent or guardian.</p>',
 'Sampleton Municipal Plaza',
 '2026-07-25 09:00:00', '2026-07-25 17:00:00', 0,
 'https://www.facebook.com/',
 'published', NULL, '2026-07-12 09:00:00'),

(3, 5, 2, 'tnr_schedule',
 'TNR Drive: Poblacion Community Cat Colony Management',
 '<p>Stray Haven in coordination with Barangay Poblacion will conduct a Trap-Neuter-Return (TNR) operation targeting the community cat colonies along Market Street and Rizal Avenue. Cats will be trapped the night before, neutered/spayed, vaccinated, ear-tipped, and returned within 24 hours.</p><p>Residents are reminded <strong>not to feed strays in the target area from July 27 at 8 PM</strong> to facilitate trapping.</p>',
 'Poblacion Market Area and Rizal Avenue',
 '2026-07-28 06:00:00', '2026-07-28 18:00:00', 0,
 NULL,
 'published', NULL, '2026-07-14 07:00:00'),

(4, 2, NULL, 'general',
 'Reminder: All Dogs Must Be Registered and Vaccinated',
 '<p>The local government reminds all pet owners that <strong>dog registration and annual anti-rabies vaccination are mandatory</strong> under RA 9482. Unregistered dogs are subject to impoundment. Registration may be done at your barangay hall from Monday to Friday, 8 AM to 5 PM. Bring your pet\'s previous vaccination record if available.</p>',
 NULL, NULL, NULL, 0, NULL,
 'published', NULL, '2026-07-01 08:00:00'),

(5, 2, 1, 'welfare_operation',
 'DRAFT: Barangay San Isidro Stray Dog Clearing Operation',
 '<p>Scheduled welfare clearing in coordination with the city veterinarian\'s office. Details TBD.</p>',
 'San Isidro Barangay',
 '2026-08-10 07:00:00', NULL, 0, NULL,
 'draft', NULL, NULL),

(6, 4, NULL, 'adoption_event',
 'Online Cat Adoption Fair — Apply Now!',
 '<p>Can\'t make it in person? Paw Rescue PH will host an <strong>online adoption fair</strong> on August 10, 2026 via Facebook Live. Prospective adopters can browse available cats, ask questions, and submit adoption applications through RePawter. Interviews and home checks will follow.</p>',
 'Online — Facebook Live',
 '2026-08-10 14:00:00', '2026-08-10 17:00:00', 0,
 'https://www.facebook.com/',
 'scheduled', '2026-08-05 08:00:00', NULL);

-- ── reports ──────────────────────────────────────────────────
INSERT INTO `reports`
    (`id`, `reporter_id`, `barangay_id`, `animal_type`, `title`, `description`,
     `location_address`, `location_landmark`, `urgency`, `photo_path`, `photo_original_name`,
     `status`, `is_locked`, `is_duplicate_of`, `rejection_reason`,
     `assigned_to`, `assigned_at`, `verified_by`, `verified_at`, `resolved_at`, `archived_at`,
     `created_at`, `updated_at`)
VALUES
-- R1: dog/critical/submitted (freshly reported, needs review)
(1, 6, 1, 'dog', 'Injured Dog on San Isidro Road',
 'There is a large brown dog lying near the road in front of the sari-sari store on San Isidro Main Road. It appears to have been hit by a vehicle — it cannot stand on its hind legs. It has been there for about an hour and is whimpering. Please respond urgently.',
 '123 San Isidro Main Road, near Ate Nena\'s Store', 'In front of the sari-sari store with the yellow awning',
 'critical', 'reports/sample.jpg', 'injured_dog.jpg',
 'submitted', 0, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL,
 '2026-07-14 07:30:00', '2026-07-14 07:30:00'),

-- R2: cat/high/under_review (being reviewed, partially checked)
(2, 7, 2, 'cat', 'Sick Cat Near Poblacion Elementary',
 'I spotted a thin, sickly-looking orange tabby cat near the gates of Poblacion Elementary School. It has visible sores on its back and seems very weak — barely moving. There are children around and I am worried about rabies. Please check this out.',
 'Rizal Street, in front of Poblacion Elementary School', 'By the school fence on the right side of the main gate',
 'high', 'reports/sample.jpg', 'sick_cat.jpg',
 'under_review', 1, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL,
 '2026-07-13 14:00:00', '2026-07-13 15:00:00'),

-- R3: dog/medium/assigned (verified then assigned to welfare org)
(3, 6, 1, 'dog', 'Stray Dog Pack Near San Isidro Market',
 'There is a pack of about 4-5 stray dogs near the San Isidro public market. They are not aggressive during the day but residents are concerned they could become dangerous at feeding time. Two of the dogs appear to be nursing females. Needs TNR attention.',
 'San Isidro Public Market, Maligaya Street', 'Near the wet goods section of the market',
 'medium', 'reports/sample.jpg', 'stray_pack.jpg',
 'assigned', 1, NULL, NULL,
 4, '2026-07-12 11:00:00', 2, '2026-07-12 10:30:00', NULL, NULL,
 '2026-07-11 09:00:00', '2026-07-12 11:00:00'),

-- R4: dog/high/in_progress (has a case, under care)
(4, 7, 2, 'dog', 'Badly Wounded Dog Found Near Creek',
 'I found a medium-sized brown dog with a deep wound on its left side near the creek behind Poblacion market. It looks like it was attacked by another animal. It is in pain but let me come close. I gave it water. Please send someone to rescue it.',
 'Behind Poblacion Wet Market, near the Sampleton Creek bridge', 'Under the acacia tree beside the creek access road',
 'high', 'reports/sample.jpg', 'wounded_dog.jpg',
 'in_progress', 1, NULL, NULL,
 4, '2026-07-10 13:00:00', 2, '2026-07-10 12:30:00', NULL, NULL,
 '2026-07-10 10:00:00', '2026-07-10 13:00:00'),

-- R5: cat/low/resolved (TNR case, released)
(5, 6, 3, 'cat', 'Community Cats at Santa Cruz Chapel',
 'There is a small colony of about 6 cats living near the back garden of Santa Cruz Chapel. The parish caretaker feeds them but they are unneutered and the population keeps growing. The cats seem healthy but need TNR management.',
 'Santa Cruz Chapel, Sampaguita Street', 'Behind the chapel near the garden wall',
 'low', 'reports/sample.jpg', 'chapel_cats.jpg',
 'resolved', 1, NULL, NULL,
 5, '2026-07-05 09:00:00', 3, '2026-07-05 08:30:00', '2026-07-08 16:00:00', NULL,
 '2026-07-04 15:00:00', '2026-07-08 16:00:00'),

-- R6: duplicate/rejected
(6, 8, 2, 'cat', 'Sick Orange Cat at School',
 'Orange sick cat near Poblacion Elementary School. Looks diseased. Someone should do something.',
 'Poblacion Elementary School, Rizal Street', 'At the school entrance',
 'high', 'reports/sample.jpg', 'school_cat2.jpg',
 'rejected', 1, 2, 'This report appears to be a duplicate of an existing open report (Report #2) for the same animal and location. Please check the status of the original report before submitting a new one.',
 NULL, NULL, NULL, NULL, NULL, NULL,
 '2026-07-13 16:00:00', '2026-07-13 17:00:00'),

-- R7: archived
(7, 7, 2, 'dog', 'Lost Dog Poster — Brown Aspin',
 'I think I saw someone\'s lost dog wandering near the Poblacion plaza. It had a collar but no tag. Posted on our community group and no one claimed it. Reporting here in case it is a stray.',
 'Poblacion Municipal Plaza', 'Near the flagpole',
 'low', 'reports/sample.jpg', 'lost_dog.jpg',
 'archived', 1, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, '2026-07-02 10:00:00',
 '2026-06-30 11:00:00', '2026-07-02 10:00:00');

-- ── report_checklist_responses (for R2 — partial review) ─────
INSERT INTO `report_checklist_responses`
    (`report_id`, `criteria_id`, `is_met`, `note`, `checked_by`, `checked_at`)
VALUES
(2, 1, 1, 'Clear photo attached showing the animal.', 2, '2026-07-13 15:10:00'),
(2, 2, 1, 'Location confirmed in Barangay Poblacion.', 2, '2026-07-13 15:12:00'),
(2, 3, 1, 'Description adequately describes the situation.', 2, '2026-07-13 15:13:00'),
(2, 4, 0, 'Still checking for possible duplicates in the system.', 2, '2026-07-13 15:15:00');

-- ── report_status_history ─────────────────────────────────────
INSERT INTO `report_status_history` (`report_id`, `from_status`, `to_status`, `changed_by`, `note`, `created_at`)
VALUES
-- R2: submitted → under_review
(2, 'submitted', 'under_review', 2, 'Report received and assigned for review by Barangay Poblacion official.', '2026-07-13 15:00:00'),
-- R3: submitted → under_review → verified → assigned
(3, 'submitted',    'under_review', 2, 'Received and queued for verification.', '2026-07-11 10:00:00'),
(3, 'under_review', 'verified',     2, 'All criteria met. Situation confirmed by barangay patrol.', '2026-07-12 10:30:00'),
(3, 'verified',     'assigned',     2, 'Assigned to Paw Rescue PH for intervention.', '2026-07-12 11:00:00'),
-- R4: submitted → under_review → verified → assigned → in_progress
(4, 'submitted',    'under_review', 2, 'Report received for review.', '2026-07-10 10:30:00'),
(4, 'under_review', 'verified',     2, 'Urgency confirmed — immediate rescue required.', '2026-07-10 12:30:00'),
(4, 'verified',     'assigned',     2, 'Assigned to Paw Rescue PH.', '2026-07-10 13:00:00'),
(4, 'assigned',     'in_progress',  4, 'Rescue team dispatched. Animal retrieved and under initial veterinary care.', '2026-07-10 14:30:00'),
-- R5: submitted → under_review → verified → assigned → in_progress → resolved
(5, 'submitted',    'under_review', 3, 'Received.', '2026-07-04 16:00:00'),
(5, 'under_review', 'verified',     3, 'Verified — classic community cat colony, good candidate for TNR.', '2026-07-05 08:30:00'),
(5, 'verified',     'assigned',     3, 'Assigned to Stray Haven for TNR.', '2026-07-05 09:00:00'),
(5, 'assigned',     'in_progress',  5, 'TNR operation in progress.', '2026-07-06 07:00:00'),
(5, 'in_progress',  'resolved',     5, 'TNR completed for 6 cats. All returned to colony. Case closed.', '2026-07-08 16:00:00'),
-- R6: submitted → rejected (duplicate)
(6, 'submitted',    'rejected',     2, 'Duplicate of Report #2.', '2026-07-13 17:00:00'),
-- R7: submitted → archived
(7, 'submitted',    'archived',     2, 'No further follow-up needed. Situation resolved informally by community.', '2026-07-02 10:00:00');

-- ── pets ─────────────────────────────────────────────────────
INSERT INTO `pets`
    (`id`, `case_id`, `created_by`, `name`, `animal_type`, `breed`, `sex`,
     `approx_age_months`, `size`, `color`, `is_vaccinated`, `is_neutered`,
     `description`, `photo_path`, `status`, `published_at`)
VALUES
-- Brownie: rescued from R4 case (case_id set after cases insert)
(1, NULL, 4, 'Brownie', 'dog', 'Aspin (Mixed Breed)', 'male',
 24, 'medium', 'Brown with white chest patch', 1, 1,
 'Brownie is a gentle, medium-sized Aspin rescued after being found injured near a creek. He has fully recovered and is now playful and affectionate. He is good with older children and calm adults. House-trained and leash-trained.',
 'pets/sample.jpg', 'adopted', '2026-07-13 09:00:00'),

-- Mingming: available, no case
(2, NULL, 5, 'Mingming', 'cat', 'Domestic Shorthair', 'female',
 18, 'small', 'Orange tabby', 1, 1,
 'Mingming is a social and curious orange tabby who loves lap time. She was rescued from the community and has been fully vaccinated and spayed. She gets along well with other cats and is litter-trained.',
 'pets/sample.jpg', 'available', '2026-07-12 10:00:00'),

-- Whitey: draft, not yet published
(3, NULL, 4, 'Whitey', 'dog', 'Aspin (Mixed Breed)', 'female',
 6, 'small', 'White with brown spots', 0, 0,
 'Whitey is a young female puppy who needs to complete her vaccination series before she is cleared for adoption. She is friendly, energetic, and loves to play.',
 'pets/sample.jpg', 'draft', NULL);

-- ── cases ─────────────────────────────────────────────────────
INSERT INTO `cases`
    (`id`, `report_id`, `case_number`, `status`, `animal_type`, `animal_name`,
     `managed_by`, `pet_profile_id`, `resolution`, `resolution_notes`,
     `opened_at`, `closed_at`)
VALUES
-- Case for R4 — under care (links to Brownie, pet_profile_id=1)
(1, 4, 'CASE-2026-000001', 'under_care', 'dog', 'Brownie (recovered)',
 4, 1, NULL, NULL,
 '2026-07-10 14:30:00', NULL),

-- Case for R5 — released (TNR)
(2, 5, 'CASE-2026-000002', 'released', 'cat', 'Chapel Cat Colony',
 5, NULL, 'TNR completed', 'Six cats from the Santa Cruz Chapel colony were trapped, spayed/neutered, vaccinated, ear-tipped, and returned to the colony. A volunteer feeder has been registered.',
 '2026-07-06 07:00:00', '2026-07-08 16:00:00');

-- Link pets.case_id to cases (now that cases exist)
UPDATE `pets` SET `case_id` = 1 WHERE `id` = 1;

-- ── case_updates ─────────────────────────────────────────────
INSERT INTO `case_updates` (`case_id`, `from_status`, `to_status`, `note`, `created_by`, `created_at`)
VALUES
-- Case 1 (Brownie)
(1, 'open',         'triaged',    'Animal assessed on arrival. Wound on left flank, approximately 5 cm laceration, not life-threatening. Administered pain relief and antibiotics.', 4, '2026-07-10 15:00:00'),
(1, 'triaged',      'rescued',    'Animal stable and transported to Paw Rescue PH facility in Sampleton.', 4, '2026-07-10 17:00:00'),
(1, 'rescued',      'under_care', 'Wound sutured and dressed. Animal resting comfortably. Named Brownie by shelter staff. Expected recovery: 2–3 weeks.', 4, '2026-07-11 09:00:00'),
-- Case 2 (Chapel Cats)
(2, 'open',         'triaged',    'Colony assessed — 6 cats identified (3 male, 3 female). Traps set overnight.', 5, '2026-07-06 07:00:00'),
(2, 'triaged',      'rescued',    '5 of 6 cats trapped successfully. Sixth cat too wary; will be targeted in follow-up.', 5, '2026-07-07 06:00:00'),
(2, 'rescued',      'released',   'All 5 cats spayed/neutered, vaccinated (FVRCP + rabies), ear-tipped, and returned. Sixth cat previously determined to be already ear-tipped by another org.', 5, '2026-07-08 16:00:00');

-- ── foster_applications ───────────────────────────────────────
INSERT INTO `foster_applications`
    (`id`, `applicant_id`, `status`, `preferred_animal_type`, `preferred_notes`,
     `household_capacity`, `current_foster_count`, `has_yard`, `household_notes`,
     `reviewed_by`, `reviewed_at`, `decision_notes`)
VALUES
-- Maria (7): approved foster
(1, 7, 'approved', 'cat',
 'I prefer adult cats or kittens. I have experience with special-needs cats.',
 2, 1, 0, 'I live in a 2-bedroom condo. Quiet environment, no children or other pets currently.',
 4, '2026-07-05 14:00:00', 'Applicant has prior fostering experience. Condo is cat-safe. Approved for up to 2 cats simultaneously.'),

-- Juan (6): submitted, pending review
(2, 6, 'submitted', 'dog',
 'I can handle medium to large dogs. I have a yard and walk regularly.',
 1, 0, 1, 'I live in a single house with a small fenced yard in San Isidro.',
 NULL, NULL, NULL);

-- ── adoption_applications ─────────────────────────────────────
-- Maria (7) adopts Brownie (pet 1): completed
INSERT INTO `adoption_applications`
    (`id`, `pet_id`, `applicant_id`, `status`, `message`, `home_type`, `has_other_pets`,
     `reviewed_by`, `reviewed_at`, `decision_notes`, `completed_at`)
VALUES
(1, 1, 7, 'completed',
 'I have been fostering animals for over a year and I fell in love with Brownie when I visited the shelter. I have a condo but walk daily and can provide lots of exercise and attention. I have no other pets currently.',
 'Condominium unit', 0,
 4, '2026-07-13 10:00:00', 'Applicant is an approved foster with excellent references. Application approved. Home visit confirmed unit is pet-safe.',
 '2026-07-13 16:00:00');

-- Juan (6) applies for Mingming (pet 2): submitted
INSERT INTO `adoption_applications`
    (`id`, `pet_id`, `applicant_id`, `status`, `message`, `home_type`, `has_other_pets`,
     `reviewed_by`, `reviewed_at`, `decision_notes`, `completed_at`)
VALUES
(2, 2, 6, 'submitted',
 'I would love to give Mingming a good home. My wife and I have always had cats. We live in a house with a yard but will keep Mingming as an indoor cat.',
 'Single family house', 0,
 NULL, NULL, NULL, NULL);

-- ── adoption_agreements ───────────────────────────────────────
INSERT INTO `adoption_agreements`
    (`id`, `adoption_application_id`, `pet_id`, `adopter_id`, `agreement_number`, `pdf_path`, `generated_by`, `generated_at`, `created_at`)
VALUES
(1, 1, 1, 7, 'ADOPT-2026-000001', 'agreements/ADOPT-2026-000001.pdf', 4, '2026-07-13 16:30:00', '2026-07-13 16:30:00');

-- Mark Brownie as adopted
UPDATE `pets` SET `status` = 'adopted' WHERE `id` = 1;

-- ── notifications ─────────────────────────────────────────────
INSERT INTO `notifications`
    (`id`, `user_id`, `type`, `title`, `body`, `related_type`, `related_id`, `link_url`, `read_at`, `created_at`)
VALUES
(1, 6, 'report_update', 'Your report is now under review',
 'Your report "Injured Dog on San Isidro Road" (Report #1) has been received and is currently under review by a barangay official.',
 'report', 1, '/reports/view.php?id=1', NULL, '2026-07-14 07:35:00'),

(2, 6, 'report_update', 'Report #3 has been verified',
 'Your report "Stray Dog Pack Near San Isidro Market" has been verified and assigned to Paw Rescue PH for intervention.',
 'report', 3, '/reports/view.php?id=3', '2026-07-12 12:00:00', '2026-07-12 11:05:00'),

(3, 7, 'adoption_update', 'Your adoption application has been approved!',
 'Congratulations! Your adoption application for Brownie has been approved. Please coordinate with Paw Rescue PH to complete the adoption process.',
 'adoption_application', 1, '/adoption/application.php?id=1', '2026-07-13 17:00:00', '2026-07-13 10:05:00');

-- ── email_outbox ──────────────────────────────────────────────
INSERT INTO `email_outbox`
    (`id`, `user_id`, `to_email`, `subject`, `body`, `type`, `related_type`, `related_id`, `status`, `queued_at`, `sent_at`, `created_at`)
VALUES
(1, 6, 'juan.delacruz@example.test',
 'Your report is now under review — RePawter',
 'Hi Juan Dela Cruz, your report "Injured Dog on San Isidro Road" (Report #1) has been received and is currently under review by a barangay official. You can track its progress at http://localhost/repawter/reports/view.php?id=1.',
 'report_update', 'report', 1, 'sent', '2026-07-14 07:35:00', '2026-07-14 07:35:01', '2026-07-14 07:35:00'),

(2, 6, 'juan.delacruz@example.test',
 'Report #3 verified — RePawter',
 'Hi Juan Dela Cruz, your report "Stray Dog Pack Near San Isidro Market" has been verified and assigned to Paw Rescue PH for intervention. Track it at http://localhost/repawter/reports/view.php?id=3.',
 'report_update', 'report', 3, 'sent', '2026-07-12 11:05:00', '2026-07-12 11:05:01', '2026-07-12 11:05:00'),

(3, 7, 'maria.santos@example.test',
 'Adoption application approved — RePawter',
 'Hi Maria Santos, congratulations! Your adoption application for Brownie has been approved. Please coordinate with Paw Rescue PH to complete the process. View your application at http://localhost/repawter/adoption/application.php?id=1.',
 'adoption_update', 'adoption_application', 1, 'sent', '2026-07-13 10:05:00', '2026-07-13 10:05:02', '2026-07-13 10:05:00');

-- ── audit_logs ────────────────────────────────────────────────
INSERT INTO `audit_logs`
    (`actor_id`, `target_user_id`, `action`, `entity_type`, `entity_id`, `description`, `metadata_json`, `ip_address`, `user_agent`, `created_at`)
VALUES
(1, 2, 'user_created', 'user', 2, 'Created barangay_official account for Ana Reyes (Brgy San Isidro)',
 '{"role":"barangay_official","barangay_id":1}', '127.0.0.1', 'RepawterSeed/1.0', '2026-01-05 09:00:00'),

(1, 3, 'user_created', 'user', 3, 'Created barangay_official account for Ben Cruz (Brgy Poblacion)',
 '{"role":"barangay_official","barangay_id":2}', '127.0.0.1', 'RepawterSeed/1.0', '2026-01-05 09:05:00'),

(1, 4, 'user_created', 'user', 4, 'Created welfare_org account for Paw Rescue PH',
 '{"role":"welfare_org","organization_name":"Paw Rescue PH"}', '127.0.0.1', 'RepawterSeed/1.0', '2026-01-10 10:00:00'),

(1, 5, 'user_created', 'user', 5, 'Created welfare_org account for Stray Haven',
 '{"role":"welfare_org","organization_name":"Stray Haven"}', '127.0.0.1', 'RepawterSeed/1.0', '2026-01-10 10:10:00'),

(2, 8, 'account_flagged', 'user', 8, 'Account flagged: Multiple duplicate reports submitted',
 '{"reason":"Multiple duplicate reports","flagged_by_role":"barangay_official"}', '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0)', '2026-07-13 17:05:00'),

(1, NULL, 'verification_criteria_changed', 'verification_criteria', NULL, 'Loaded initial set of 6 verification criteria for report review process',
 '{"count":6,"action":"bulk_insert"}', '127.0.0.1', 'RepawterSeed/1.0', '2026-01-15 08:00:00');

SET FOREIGN_KEY_CHECKS = 1;
