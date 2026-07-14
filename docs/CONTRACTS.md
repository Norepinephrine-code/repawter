# RePawter — Shared Developer Contracts

> **This file is the single source of truth for all packages (B through H).**
> Every contract listed here is enforced exactly as written. Do not deviate.

---

## 1. Serving Model

The project root (`D:\SchoolCode\paw_itprog_mp`) is junctioned to `C:\xampp\htdocs\repawter` and served at `http://localhost/repawter/`. The project root **IS** the web root.

```
BASE_URL = '/repawter'
```

### Bootstrap Require Rule

Every PHP file must require bootstrap by counting how many directory levels it sits below the web root and prepending that many `../` hops:

| File location | Require statement |
|---|---|
| `/index.php` | `require __DIR__ . '/app/bootstrap.php';` |
| `/reports/submit.php` | `require __DIR__ . '/../app/bootstrap.php';` |
| `/admin/reports/index.php` | `require __DIR__ . '/../../app/bootstrap.php';` |
| `/actions/reports/report_submit.php` | `require __DIR__ . '/../../app/bootstrap.php';` |
| `/admin/system/criteria.php` | `require __DIR__ . '/../../app/bootstrap.php';` |

The bootstrap file lives at `APP_ROOT/app/bootstrap.php`. `APP_ROOT` is the project root (defined in config as `dirname(__DIR__, 2)` from `app/config/config.php`, or `dirname(__DIR__)` from `app/bootstrap.php`).

---

## 2. Role Constants and Ability Map

### Constants (defined in `app/core/rbac.php`)

```php
const ROLE_RESIDENT = 'community_resident';
const ROLE_OFFICIAL = 'barangay_official';
const ROLE_WELFARE  = 'welfare_org';
const ROLE_ADMIN    = 'system_admin';
```

### `can(string $ability): bool` — Full Ability Map

| Ability | Roles Permitted |
|---|---|
| `manage_reports` | `barangay_official`, `welfare_org`, `system_admin` |
| `review_foster` | `welfare_org`, `barangay_official`, `system_admin` |
| `review_adoption` | `welfare_org`, `system_admin` |
| `manage_pets` | `welfare_org`, `system_admin` |
| `manage_announcements` | `barangay_official`, `welfare_org`, `system_admin` |
| `manage_users` | `barangay_official`, `system_admin` |
| `edit_criteria` | `system_admin` |
| `view_analytics` | `barangay_official`, `welfare_org`, `system_admin` |
| `view_audit` | `system_admin` |
| `manage_resources` | `system_admin`, `barangay_official`, `welfare_org` |
| `track_cases` | `barangay_official`, `welfare_org`, `system_admin` |

---

## 3. Session Keys

The session stores the following keys:

### `$_SESSION['user']` — shape

```php
[
    'id'                => int,           // users.id
    'role'              => string,        // one of the ROLE_* constants
    'full_name'         => string,        // users.full_name
    'email'             => string,        // users.email
    'barangay_id'       => int|null,      // users.barangay_id
    'account_status'    => string,        // 'active' | 'flagged' | 'suspended'
    'organization_name' => string|null,   // users.organization_name
]
```

### Other session keys

| Key | Purpose |
|---|---|
| `$_SESSION['_csrf']` | CSRF token string (hex, 64 chars) |
| `$_SESSION['_flash']` | Array of `['type'=>string, 'msg'=>string]` for flash messages |
| `$_SESSION['_old']` | Array of previous POST input for repopulating forms after errors |
| `$_SESSION['intended_url']` | URL the user was trying to reach before being redirected to login |

---

## 4. Core Function Reference (`app/core/`)

All functions are global (plain PHP functions, no namespaces). Use PDO prepared statements exclusively.

### db.php

| Function | Signature | Behavior |
|---|---|---|
| `db` | `db(): PDO` | Lazy singleton PDO. DSN: mysql utf8mb4. Options: ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES=false. On connect: `SET time_zone = '+08:00'`. |
| `db_one` | `db_one(string $sql, array $p = []): ?array` | Returns first matching row as assoc array, or `null` if no match. |
| `db_all` | `db_all(string $sql, array $p = []): array` | Returns all matching rows as array of assoc arrays. |
| `db_exec` | `db_exec(string $sql, array $p = []): int` | Executes a mutating query; returns affected row count. |
| `db_insert_id` | `db_insert_id(): string` | Returns `lastInsertId()` as string. Call immediately after `db_exec` for INSERT. |

### session.php

| Function | Signature | Behavior |
|---|---|---|
| `session_boot` | `session_boot(): void` | Starts session with httponly + samesite=Lax cookie params. Idempotent. |
| `current_user` | `current_user(): ?array` | Returns `$_SESSION['user']` or null. |
| `is_logged_in` | `is_logged_in(): bool` | True if `$_SESSION['user']['id']` is set. |
| `user_id` | `user_id(): ?int` | Returns current user id as int, or null. |
| `user_role` | `user_role(): ?string` | Returns current user role string, or null. |
| `set_current_user` | `set_current_user(array $u): void` | Stores normalized user data in session. |
| `logout` | `logout(): void` | Clears session data, unsets cookie, destroys session. |

### auth.php

| Function | Signature | Behavior |
|---|---|---|
| `hash_password` | `hash_password(string $p): string` | Returns `password_hash($p, PASSWORD_DEFAULT)`. |
| `attempt_login` | `attempt_login(string $email, string $pw): array\|false` | Fetches user by email; verifies password; returns false if suspended or credentials wrong; on success updates `last_login_at` and returns user row array. |
| `register_resident` | `register_resident(array $d): int\|array` | Validates email uniqueness + required fields; inserts `community_resident` with `active` status; returns new id on success or `['errors' => [...]]` on failure. |

### rbac.php

| Function | Signature | Behavior |
|---|---|---|
| `require_login` | `require_login(): void` | Redirects to `/auth/login.php` if not logged in; stores intended URL in session. |
| `require_role` | `require_role(string ...$roles): void` | Calls `require_login()` first; if user role not in `$roles`, sets HTTP 403, includes `403.php`, exits. |
| `can` | `can(string $ability): bool` | Returns true if current user's role is in the ability map for `$ability`. Returns false if not logged in. |

### csrf.php

| Function | Signature | Behavior |
|---|---|---|
| `csrf_token` | `csrf_token(): string` | Generates and stores a 64-char hex token in `$_SESSION['_csrf']` if absent; returns it. |
| `csrf_field` | `csrf_field(): string` | Returns `<input type="hidden" name="_csrf" value="...">` with the token. |
| `csrf_verify` | `csrf_verify(): void` | Compares `$_POST['_csrf']` with session token via `hash_equals`; on failure flashes error and redirects to referer (or home), then exits. |

### flash.php

| Function | Signature | Behavior |
|---|---|---|
| `flash` | `flash(string $type, string $msg): void` | Appends `['type','msg']` to `$_SESSION['_flash']`. Valid types: `success`, `error`, `warning`, `info`. |
| `get_flashes` | `get_flashes(): array` | Returns and clears `$_SESSION['_flash']`. Called in `layouts/header.php`. |
| `flash_old` | `flash_old(array $input): void` | Stores form input in `$_SESSION['_old']` for form repopulation. |
| `old_input` | `old_input(): array` | Returns and clears `$_SESSION['_old']`. Called in `layout_footer()`. |
| `peek_old` | `peek_old(): array` | Returns `$_SESSION['_old']` without clearing it. Used by `old()`. |

### validation.php

| Function | Signature | Behavior |
|---|---|---|
| `sanitize_string` | `sanitize_string(?string $v): string` | Returns `trim((string)$v)`. |
| `validate` | `validate(array $input, array $rules): array` | Returns `[$clean, $errors]`. Rule string format: `'required\|email\|max:255'`, `'int\|min:0'`, `'in:dog,cat,other'`, `'nullable'`. Each rule is pipe-delimited. |
| `old` | `old(string $key, string $default = ''): string` | Returns value from `peek_old()` for the given key, or default. Does not clear old input. |

### upload.php

| Function | Signature | Behavior |
|---|---|---|
| `handle_upload` | `handle_upload(array $file, string $subdir): array` | Returns `['ok'=>bool, 'path'=>?string, 'error'=>?string]`. Validates UPLOAD_ERR_OK, size ≤ MAX_UPLOAD_BYTES, MIME via `finfo_file`, `getimagesize()` success (no GD). Derives extension from MIME (jpeg→jpg, png→png, webp→webp). Filename: `bin2hex(random_bytes(16)) . '.' . $ext`. Target: `UPLOAD_DIR/$subdir/`. Returns relative path `"$subdir/$filename"`. |
| `upload_url` | `upload_url(string $rel): string` | Returns `UPLOAD_URL . '/' . $rel`. |
| `delete_upload` | `delete_upload(string $rel): void` | Deletes file at `UPLOAD_DIR/$rel` if it exists. |

### notify.php

| Function | Signature | Behavior |
|---|---|---|
| `notify` | `notify(int $userId, string $type, string $title, string $body, ?string $link=null, ?string $relatedType=null, ?int $relatedId=null): int` | INSERTs into `notifications` AND `email_outbox` (user's email, status='sent', sent_at=NOW()); returns notification id. |
| `notify_role` | `notify_role(string $role, string $type, string $title, string $body, ?string $link=null): int` | Calls `notify()` for every active user of that role; returns count of notifications sent. |
| `unread_count` | `unread_count(int $userId): int` | COUNT of notifications where `user_id=$userId AND read_at IS NULL`. |
| `mark_read` | `mark_read(int $id, int $userId): void` | Sets `read_at=NOW()` for a single notification belonging to the user. |
| `mark_all_read` | `mark_all_read(int $userId): void` | Sets `read_at=NOW()` for all unread notifications for the user. |

### pagination.php

| Function | Signature | Behavior |
|---|---|---|
| `paginate` | `paginate(string $sql, array $params, int $page, int $perPage=15): array` | Wraps `$sql` for COUNT; appends LIMIT/OFFSET; returns `['rows'=>array, 'total'=>int, 'page'=>int, 'pages'=>int, 'perPage'=>int]`. |
| `render_pager` | `render_pager(array $p, string $urlPattern): string` | Returns Bootstrap pagination HTML. `$urlPattern` contains `%d` for the page number. Returns empty string if `$p['pages'] <= 1`. |

### view.php

| Function | Signature | Behavior |
|---|---|---|
| `e` | `e(mixed $v): string` | `htmlspecialchars((string)($v ?? ''), ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`. Use on ALL user output. |
| `url` | `url(string $path = ''): string` | If `$path` starts with `http://` or `https://`, returns as-is. Otherwise prepends `BASE_URL`. |
| `asset` | `asset(string $path): string` | Returns `BASE_URL . '/assets' . $path`. |
| `redirect` | `redirect(string $path): void` | `header('Location: ' . url($path)); exit;` |
| `layout_header` | `layout_header(string $title = '', string $activeNav = ''): void` | Includes `app/views/layouts/header.php`. Renders doctype, head, navbar, flash messages, opens `<main>`. |
| `layout_footer` | `layout_footer(array $extraJs = []): void` | Calls `old_input()` to clear old input; includes `app/views/layouts/footer.php`. Closes `</main>`, renders footer, scripts. |
| `partial` | `partial(string $name, array $data = []): void` | Extracts `$data` into scope; includes `app/views/partials/$name.php`. |
| `admin_nav` | `admin_nav(string $active = ''): void` | Includes `app/views/layouts/admin_nav.php`. |

### audit.php

| Function | Signature | Behavior |
|---|---|---|
| `audit_log` | `audit_log(string $action, ?string $entityType=null, ?int $entityId=null, ?array $meta=null, ?int $targetUserId=null): void` | INSERTs into `audit_logs`. Sets `actor_id=user_id()`, `ip_address=$_SERVER['REMOTE_ADDR']`, `user_agent`, `metadata_json=json_encode($meta)`. |

---

## 5. Database Tables — Full Column and ENUM Reference

All tables: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`. All PKs: `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`. All tables have `created_at`. Mutable tables also have `updated_at` (ON UPDATE CURRENT_TIMESTAMP). Append-only tables (report_status_history, case_updates, notifications, email_outbox, audit_logs) have only `created_at`.

### 1. `barangays`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| name | VARCHAR(120) NN |
| municipality | VARCHAR(120) NULL |
| province | VARCHAR(120) NULL |
| is_active | TINYINT(1) NN DEFAULT 1 |
| created_at | DATETIME NN DEFAULT CURRENT_TIMESTAMP |

### 2. `users`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| role | ENUM('community_resident','barangay_official','welfare_org','system_admin') NN DEFAULT 'community_resident' |
| account_status | ENUM('active','flagged','suspended') NN DEFAULT 'active' |
| email | VARCHAR(255) NN UNIQUE |
| password_hash | VARCHAR(255) NN |
| full_name | VARCHAR(150) NN |
| contact_number | VARCHAR(30) NULL |
| barangay_id | BIGINT UNSIGNED NULL FK→barangays(id) SET NULL |
| organization_name | VARCHAR(150) NULL |
| flagged_reason | VARCHAR(255) NULL |
| last_login_at | DATETIME NULL |
| created_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 3. `resource_categories`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| name | VARCHAR(100) NN UNIQUE |
| slug | VARCHAR(120) NN UNIQUE |
| is_active | TINYINT(1) NN DEFAULT 1 |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 4. `verification_criteria`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| code | VARCHAR(50) NN UNIQUE |
| label | VARCHAR(200) NN |
| description | VARCHAR(500) NULL |
| is_required | TINYINT(1) NN DEFAULT 1 |
| is_active | TINYINT(1) NN DEFAULT 1 |
| sort_order | INT NN DEFAULT 0 |
| created_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 5. `reports`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| reporter_id | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| barangay_id | BIGINT UNSIGNED NULL FK→barangays(id) SET NULL |
| animal_type | ENUM(**'dog'**,'cat','other') NN DEFAULT 'dog' |
| species_other | VARCHAR(80) NULL |
| title | VARCHAR(150) NULL |
| description | TEXT NN |
| location_address | VARCHAR(255) NN |
| location_landmark | VARCHAR(255) NULL |
| latitude | DECIMAL(10,7) NULL |
| longitude | DECIMAL(10,7) NULL |
| urgency | ENUM(**'low'**,'medium','high','critical') NN DEFAULT 'medium' |
| photo_path | VARCHAR(255) NN |
| photo_original_name | VARCHAR(255) NULL |
| status | ENUM(**'submitted'**,'under_review','verified','rejected','assigned','in_progress','resolved','archived') NN DEFAULT 'submitted' |
| is_locked | TINYINT(1) NN DEFAULT 0 |
| is_duplicate_of | BIGINT UNSIGNED NULL FK→reports(id) SET NULL |
| rejection_reason | VARCHAR(500) NULL |
| assigned_to | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| assigned_at | DATETIME NULL |
| verified_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| verified_at | DATETIME NULL |
| resolved_at | DATETIME NULL |
| archived_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 6. `report_checklist_responses`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| report_id | BIGINT UNSIGNED NN FK→reports(id) CASCADE |
| criteria_id | BIGINT UNSIGNED NN FK→verification_criteria(id) RESTRICT |
| is_met | TINYINT(1) NN DEFAULT 0 |
| note | VARCHAR(500) NULL |
| checked_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| checked_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |
UNIQUE(report_id, criteria_id)

### 7. `report_status_history` (append-only)
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| report_id | BIGINT UNSIGNED NN FK→reports(id) CASCADE |
| from_status | VARCHAR(30) NULL |
| to_status | VARCHAR(30) NN |
| changed_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| note | VARCHAR(500) NULL |
| created_at | DATETIME NN |

### 8. `pets`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| case_id | BIGINT UNSIGNED NULL FK→cases(id) SET NULL |
| created_by | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| name | VARCHAR(80) NN |
| animal_type | ENUM(**'dog'**,'cat','other') NN DEFAULT 'dog' |
| species_other | VARCHAR(80) NULL |
| breed | VARCHAR(80) NULL |
| sex | ENUM(**'male'**,'female','unknown') NN DEFAULT 'unknown' |
| approx_age_months | INT UNSIGNED NULL |
| size | ENUM('small','medium','large') NULL |
| color | VARCHAR(80) NULL |
| is_vaccinated | TINYINT(1) NULL |
| is_neutered | TINYINT(1) NULL |
| description | TEXT NULL |
| photo_path | VARCHAR(255) NULL |
| status | ENUM(**'draft'**,'available','pending_adoption','adopted','archived') NN DEFAULT 'draft' |
| published_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 9. `cases`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| report_id | BIGINT UNSIGNED NN FK→reports(id) RESTRICT — UNIQUE |
| case_number | VARCHAR(30) NN UNIQUE |
| status | ENUM(**'open'**,'triaged','rescued','under_care','ready_for_adoption','fostered','adopted','released','deceased','closed') NN DEFAULT 'open' |
| animal_type | ENUM(**'dog'**,'cat','other') NN DEFAULT 'dog' |
| animal_name | VARCHAR(80) NULL |
| managed_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| pet_profile_id | BIGINT UNSIGNED NULL FK→pets(id) SET NULL |
| resolution | VARCHAR(120) NULL |
| resolution_notes | TEXT NULL |
| opened_at | DATETIME NN DEFAULT CURRENT_TIMESTAMP |
| closed_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 10. `case_updates` (append-only)
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| case_id | BIGINT UNSIGNED NN FK→cases(id) CASCADE |
| from_status | VARCHAR(30) NULL |
| to_status | VARCHAR(30) NULL |
| note | TEXT NULL |
| created_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| created_at | DATETIME NN |

### 11. `foster_applications`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| applicant_id | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| status | ENUM(**'submitted'**,'under_review','approved','rejected','withdrawn') NN DEFAULT 'submitted' |
| preferred_animal_type | ENUM(**'dog'**,'cat','other') NN DEFAULT 'dog' |
| preferred_notes | VARCHAR(500) NULL |
| household_capacity | INT UNSIGNED NN DEFAULT 1 |
| current_foster_count | INT UNSIGNED NN DEFAULT 0 |
| has_yard | TINYINT(1) NULL |
| household_notes | VARCHAR(500) NULL |
| reviewed_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| reviewed_at | DATETIME NULL |
| decision_notes | VARCHAR(500) NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 12. `adoption_applications`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| pet_id | BIGINT UNSIGNED NN FK→pets(id) RESTRICT |
| applicant_id | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| status | ENUM(**'submitted'**,'under_review','approved','rejected','withdrawn','completed') NN DEFAULT 'submitted' |
| message | TEXT NULL |
| home_type | VARCHAR(120) NULL |
| has_other_pets | TINYINT(1) NULL |
| reviewed_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| reviewed_at | DATETIME NULL |
| decision_notes | VARCHAR(500) NULL |
| completed_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |
UNIQUE(pet_id, applicant_id)

### 13. `adoption_agreements`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| adoption_application_id | BIGINT UNSIGNED NN FK→adoption_applications(id) RESTRICT — UNIQUE |
| pet_id | BIGINT UNSIGNED NN FK→pets(id) RESTRICT |
| adopter_id | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| agreement_number | VARCHAR(30) NN UNIQUE |
| pdf_path | VARCHAR(255) NN |
| generated_by | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| generated_at | DATETIME NN DEFAULT CURRENT_TIMESTAMP |
| created_at | DATETIME NN |

### 14. `announcements`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| author_id | BIGINT UNSIGNED NN FK→users(id) RESTRICT |
| barangay_id | BIGINT UNSIGNED NULL FK→barangays(id) SET NULL |
| category | ENUM(**'vaccination_drive'**,'adoption_event','tnr_schedule','welfare_operation','general') NN DEFAULT 'general' |
| title | VARCHAR(200) NN |
| body | TEXT NN |
| location_text | VARCHAR(255) NULL |
| event_start | DATETIME NULL |
| event_end | DATETIME NULL |
| is_all_day | TINYINT(1) NN DEFAULT 0 |
| facebook_url | VARCHAR(500) NULL |
| status | ENUM(**'draft'**,'scheduled','published','archived') NN DEFAULT 'draft' |
| publish_at | DATETIME NULL |
| published_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 15. `notifications` (append-only + read_at)
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| user_id | BIGINT UNSIGNED NN FK→users(id) CASCADE |
| type | VARCHAR(50) NN DEFAULT 'system' |
| title | VARCHAR(200) NN |
| body | VARCHAR(500) NULL |
| related_type | VARCHAR(30) NULL |
| related_id | BIGINT UNSIGNED NULL |
| link_url | VARCHAR(255) NULL |
| read_at | DATETIME NULL |
| created_at | DATETIME NN |

### 16. `email_outbox` (append-only)
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| user_id | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| to_email | VARCHAR(255) NN |
| subject | VARCHAR(255) NN |
| body | TEXT NN |
| type | VARCHAR(50) NN DEFAULT 'system' |
| related_type | VARCHAR(30) NULL |
| related_id | BIGINT UNSIGNED NULL |
| status | ENUM(**'queued'**,'sent','failed') NN DEFAULT 'sent' |
| error_message | VARCHAR(255) NULL |
| queued_at | DATETIME NN DEFAULT CURRENT_TIMESTAMP |
| sent_at | DATETIME NULL |
| created_at | DATETIME NN |

### 17. `educational_resources`
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| category_id | BIGINT UNSIGNED NN FK→resource_categories(id) RESTRICT |
| author_id | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| title | VARCHAR(200) NN |
| slug | VARCHAR(220) NN UNIQUE |
| body | MEDIUMTEXT NN |
| summary | VARCHAR(500) NULL |
| cover_image_path | VARCHAR(255) NULL |
| is_published | TINYINT(1) NN DEFAULT 1 |
| published_at | DATETIME NULL |
| created_at | DATETIME NN |
| updated_at | DATETIME NN ON UPDATE |

### 18. `audit_logs` (append-only)
| Column | Type |
|---|---|
| id | BIGINT UNSIGNED PK |
| actor_id | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| target_user_id | BIGINT UNSIGNED NULL FK→users(id) SET NULL |
| action | VARCHAR(50) NN |
| entity_type | VARCHAR(50) NULL |
| entity_id | BIGINT UNSIGNED NULL |
| description | VARCHAR(500) NULL |
| metadata_json | TEXT NULL |
| ip_address | VARCHAR(45) NULL |
| user_agent | VARCHAR(255) NULL |
| created_at | DATETIME NN |

---

## 6. Model Method Signatures

These are the EXACT method signatures every package must implement. Other packages call these methods — do not rename or alter signatures.

**Each model is a class with static methods**, e.g. `class UserModel { public static function find(int $id): ?array { ... } }`. Model methods use the core `db_one()/db_all()/db_exec()/db_insert_id()` helpers with prepared statements.

**Models are AUTOLOADED** — `bootstrap.php` registers an autoloader that loads `app/models/{ClassName}.php` on first use. So a page/action can call `UserModel::find(5)` directly after requiring bootstrap; do NOT manually `require` model files. (The only class NOT autoloaded is `FPDF` in `/lib/fpdf/fpdf.php` — Package F must `require` it explicitly.)

**Rule:** Models JOIN for display data (barangay name, user full_name, etc.) rather than calling another package's model. The only permitted cross-package model call is **Package D** calling `ReportModel`.

### UserModel — Owner: Package B
File: `app/models/UserModel.php`

```php
UserModel::find(int $id): ?array
UserModel::find_by_email(string $email): ?array
UserModel::create_resident(array $data): int
UserModel::create_staff(array $data): int
UserModel::update_profile(int $id, array $data): void
UserModel::update_password(int $id, string $hash): void
UserModel::list_filtered(array $filters, int $page): array   // returns paginate() result
UserModel::set_status(int $id, string $status, ?string $reason): void
UserModel::count_by_role(): array   // ['community_resident'=>n, 'barangay_official'=>n, ...]
```

### ReportModel — Owner: Package C
File: `app/models/ReportModel.php`

```php
ReportModel::create(array $data): int
ReportModel::find(int $id): ?array
ReportModel::find_with_details(int $id): ?array   // JOINs barangay, reporter, assigned_to, verified_by
ReportModel::list_by_user(int $userId, int $page): array
ReportModel::list_filtered(array $filters, int $page): array
ReportModel::update(int $id, array $data): void
ReportModel::set_status(int $id, string $newStatus, int $changedBy, ?string $note): void
    // Also writes report_status_history; sets is_locked=1 when status leaves 'submitted'
ReportModel::assign(int $id, int $assigneeId, int $by): void
ReportModel::set_verified(int $id, int $by): void
ReportModel::archive(int $id, int $by): void
ReportModel::status_history(int $reportId): array
ReportModel::is_locked(int $id): bool
ReportModel::active_criteria(): array   // returns active verification_criteria rows ordered by sort_order
ReportModel::checklist_for(int $reportId): array
ReportModel::save_checklist(int $reportId, array $responses, int $by): void
    // $responses = [criteria_id => ['is_met'=>bool, 'note'=>string], ...]
ReportModel::all_required_met(int $reportId): bool
```

### CaseModel — Owner: Package D
File: `app/models/CaseModel.php`

```php
CaseModel::create_from_report(int $reportId, int $managedBy): int
CaseModel::find(int $id): ?array
CaseModel::find_by_report(int $reportId): ?array
CaseModel::list_filtered(array $filters, int $page): array
CaseModel::update_status(int $id, string $newStatus, int $by, ?string $note): void
CaseModel::add_update(int $caseId, string $note, int $by): void
CaseModel::link_pet(int $caseId, int $petId): void
CaseModel::updates_for(int $caseId): array
```

### FosterModel — Owner: Package E
File: `app/models/FosterModel.php`

```php
FosterModel::create(array $data): int
FosterModel::find(int $id): ?array
FosterModel::find_with_applicant(int $id): ?array
FosterModel::list_by_user(int $userId, int $page): array
FosterModel::list_filtered(array $filters, int $page): array
FosterModel::decide(int $id, string $status, int $reviewerId, ?string $notes): void
FosterModel::applicant_history(int $userId): array
```

### PetModel — Owner: Package F
File: `app/models/PetModel.php`

```php
PetModel::create(array $data): int
PetModel::update(int $id, array $data): void
PetModel::find(int $id): ?array
PetModel::list_public(array $filters, int $page): array
PetModel::list_admin(array $filters, int $page): array
PetModel::set_status(int $id, string $status): void
```

### AdoptionModel — Owner: Package F
File: `app/models/AdoptionModel.php`

```php
AdoptionModel::create(array $data): int
AdoptionModel::find(int $id): ?array
AdoptionModel::find_with_details(int $id): ?array
AdoptionModel::list_by_user(int $userId, int $page): array
AdoptionModel::list_filtered(array $filters, int $page): array
AdoptionModel::decide(int $id, string $status, int $reviewerId, ?string $notes): void
AdoptionModel::complete(int $id, int $by): void
AdoptionModel::create_agreement(int $appId, string $pdfPath, int $by): int
AdoptionModel::find_agreement(int $appId): ?array
```

### AnnouncementModel — Owner: Package G
File: `app/models/AnnouncementModel.php`

```php
AnnouncementModel::create(array $data): int
AnnouncementModel::update(int $id, array $data): void
AnnouncementModel::find(int $id): ?array
AnnouncementModel::list_published(int $page): array
AnnouncementModel::list_admin(array $filters, int $page): array
AnnouncementModel::events_between(string $startIso, string $endIso): array
    // Returns rows with event_start between the two ISO datetime strings (for FullCalendar)
AnnouncementModel::publish(int $id, int $by): void
AnnouncementModel::archive(int $id): void
```

### NotificationModel — Owner: Package H
File: `app/models/NotificationModel.php`

```php
NotificationModel::list_for(int $userId, int $page): array
// Note: notify(), unread_count(), mark_read(), mark_all_read() live in app/core/notify.php
```

### ResourceModel — Owner: Package H
File: `app/models/ResourceModel.php`

```php
ResourceModel::list_published(?int $categoryId, int $page): array
ResourceModel::find(int $id): ?array
ResourceModel::find_by_slug(string $slug): ?array
ResourceModel::list_admin(int $page): array
ResourceModel::create(array $data): int
ResourceModel::update(int $id, array $data): void
ResourceModel::set_published(int $id, bool $published): void
ResourceModel::categories(): array   // returns all active resource_categories
```

---

## 7. Action Contract

Every file under `/actions/**/*.php` MUST follow this pattern — no exceptions:

```php
<?php
require __DIR__ . '/../../app/bootstrap.php';  // adjust depth

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_login();          // or require_role(ROLE_OFFICIAL, ROLE_ADMIN) etc.

[$clean, $errors] = validate($_POST, [...rules...]);
if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors below.');
    redirect('/some/form/page.php');
}

// Mutate via model
SomeModel::create($clean);

flash('success', 'Done!');
redirect('/some/page.php');
```

Rules:
- Actions output **no HTML**.
- Every `<form method="post">` must include `<?= csrf_field() ?>`.
- Actions must never be directly linkable (GET returns a redirect back).

---

## 8. Page Contract

Every page file MUST follow this pattern:

```php
<?php
require __DIR__ . '/../app/bootstrap.php';  // adjust depth

require_login();
// require_role(ROLE_OFFICIAL, ROLE_ADMIN);  // for admin pages

$data = SomeModel::find($id);

layout_header('Page Title', 'nav_key');
// For admin pages, also call:
admin_nav('nav_key');
?>
<!-- HTML here — escape all user data with e() -->
<h1><?= e($data['title']) ?></h1>
<?php
layout_footer();
```

Rules:
- Admin pages (under `/admin/`) must call `require_role(...)` and `admin_nav($active)`.
- All user-controlled output must be wrapped in `e()`.
- Pages call models for data — they do not write SQL directly.

---

## 9. CSS Classes Available (paw-theme.css)

| Class | Purpose |
|---|---|
| `.paw-navbar` | Branded dark-brown navbar |
| `.paw-hero` | Full-width gradient hero section |
| `.paw-page-title` | Underlined page heading in dark brown |
| `.paw-card` | Warm-bordered card with hover lift |
| `.btn-paw` | Primary coral/orange button |
| `.btn-paw-outline` | Outlined coral button |
| `.btn-paw-teal` | Teal action button |
| `.badge-status--submitted` | Grey badge |
| `.badge-status--under_review` | Blue badge |
| `.badge-status--verified` | Teal badge |
| `.badge-status--rejected` | Red badge |
| `.badge-status--assigned` | Purple badge |
| `.badge-status--in_progress` | Orange badge |
| `.badge-status--resolved` | Green badge |
| `.badge-status--archived` | Dark grey badge |
| `.urgency--low` | Green pill |
| `.urgency--medium` | Yellow pill |
| `.urgency--high` | Orange pill |
| `.urgency--critical` | Red pulsing pill |
| `.pet-card` | Pet listing card with hover |
| `.pet-card__img` | Pet card image (220px, object-fit:cover) |
| `.pet-card__body` | Pet card body padding |
| `.pet-card__status` | Pet card status badge |
| `.report-photo` | Report detail photo (max 340px, cover) |
| `.pet-photo` | Pet profile photo (max 380px, cover) |
| `.paw-admin-subnav` | Admin horizontal sub-navigation bar (scrolls on mobile) |
| `.paw-admin-nav-link` | Admin sub-nav item |
| `.text-paw` | Coral text color |
| `.bg-paw` | Coral background |
| `.text-paw-dark` | Dark brown text |
| `.border-paw` | Warm border color |

---

## 10. Upload Rules

1. Call `handle_upload($_FILES['photo'], 'reports')` or `handle_upload($_FILES['photo'], 'pets')`.
2. The returned `$result['path']` is a **relative path** like `"reports/abc123.jpg"` — store this string in the database column `photo_path`.
3. Render with `upload_url($pet['photo_path'])` which returns the full public URL.
4. To delete a file, call `delete_upload($relativePath)`.
5. Allowed MIME types: `image/jpeg`, `image/png`, `image/webp`. Max size: 5 MB (5,242,880 bytes).
6. Do NOT use GD functions for validation — use `getimagesize()` only.
