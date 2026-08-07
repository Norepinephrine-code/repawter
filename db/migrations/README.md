# Database migrations

Every SQL statement that defines or seeds the RePawter database lives in this
folder, one numbered file per change. The runner (`db/migrate.php`) applies them
in order and records what it has applied, so a database can be brought from
empty to current with a single command — and brought forward again later without
anyone having to remember which statements they already ran.

---

## Quick reference

```bash
# From the project root.

php db/migrate.php --status   # what is applied, what is pending
php db/migrate.php            # apply everything pending
php db/migrate.php --seed     # apply, then load demo data (never in production)
```

The database itself must already exist — the runner will not create it, because
`CREATE DATABASE` needs more privilege than a deploy account should hold:

```sql
CREATE DATABASE `repawter` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Connection settings come from the same environment variables the application
uses (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) — see
[`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md). Because DDL needs
privileges the runtime account should not have, the runner also honours
`DB_MIGRATION_USER` and `DB_MIGRATION_PASS`:

```bash
DB_MIGRATION_USER=repawter_ddl DB_MIGRATION_PASS='…' php db/migrate.php
```

---

## The migrations

Files are applied in filename order. The order is not cosmetic: each migration
creates tables that later ones reference by foreign key.

| # | File | What it adds | Why it sits here |
|---|---|---|---|
| 0001 | `0001_core_identity.sql` | `barangays`, `users` | Roots of the foreign-key graph — nearly every table points at `users`. |
| 0002 | `0002_taxonomy_and_criteria.sql` | `resource_categories`, `verification_criteria` | Admin-editable lookups that later tables reference. |
| 0003 | `0003_reporting.sql` | `reports`, `report_checklist_responses`, `report_status_history` | The core workflow; needs users, barangays and criteria to exist. |
| 0004 | `0004_cases_and_pets.sql` | `pets`, `cases`, `case_updates` | Case tracking. Resolves the circular `pets` ↔ `cases` reference (see below). |
| 0005 | `0005_applications.sql` | `foster_applications`, `adoption_applications`, `adoption_agreements` | Foster and adoption flows; adoption rows reference `pets`. |
| 0006 | `0006_engagement.sql` | `announcements`, `notifications`, `email_outbox`, `educational_resources` | Outward communication and the article library. |
| 0007 | `0007_audit.sql` | `audit_logs` | Append-only privileged-action trail. |
| 0008 | `0008_reference_data.sql` | Checklist criteria, resource categories | Data the app cannot function without, as opposed to demo content. |

### The one non-obvious bit: `pets` ↔ `cases`

The two tables reference each other:

```
cases.pet_profile_id  →  pets.id     the published profile for this case
pets.case_id          →  cases.id    the case this animal came from
```

No `CREATE TABLE` order satisfies both. Migration 0004 creates `pets` without
its case foreign key, creates `cases`, then adds the missing constraint by
`ALTER TABLE`. That `ALTER` is wrapped in an `information_schema` check because
MySQL 8 has no `ADD CONSTRAINT IF NOT EXISTS`, and re-running the file would
otherwise fail with a duplicate-constraint error.

---

## Reference data vs. demo data

Two different things, deliberately kept apart:

- **`0008_reference_data.sql`** — rows the application genuinely needs. Without
  at least one active verification criterion, no official can verify a report.
  Safe, and intended, for production.
- **`../seed.sql`** — eight demo accounts, seven sample reports, sample pets and
  announcements. For development and demonstration only.
  `php db/migrate.php --seed` refuses to load it when `APP_ENV=prod`.

Barangays are deliberately *not* in 0008: they are specific to the municipality
deploying the system. Add your own as a new migration — there is a template at
the bottom of `0008_reference_data.sql`. `seed.sql` supplies five fictional ones
for local development.

---

## Adding a migration

1. Create `NNNN_short_description.sql`, taking the next free number.
2. Write forward-only DDL. Prefer `CREATE TABLE IF NOT EXISTS`; for `ALTER`,
   guard with an `information_schema` check as 0004 does, so re-running is safe.
3. Comment *why*, not what. `ADD COLUMN is_locked` explains itself; "reports lock
   once review starts so the reporter cannot edit evidence" does not.
4. Run `php db/migrate.php --status`, then `php db/migrate.php`.
5. Keep `../schema.sql` in step (see below).

**Never edit a migration that has already been applied anywhere.** The runner
stores a SHA-256 of each file and prints a `CHANGED` state for any that no longer
matches, because your database and the file have silently diverged — the edit was
never executed. Write a new migration instead.

### Naming

`NNNN_snake_case_description.sql`, zero-padded to four digits. The leading
number is the version recorded in `schema_migrations`; anything after the first
underscore is for humans. A file that does not match `^\d+_` is skipped with a
warning.

---

## Relationship to `../schema.sql`

`db/schema.sql` is a flat snapshot of the full current schema. It exists because
the README's XAMPP quick-start imports it in one command, which is friendlier
than running a PHP script for a first-time local setup.

**The migrations are authoritative.** `schema.sql` is a convenience copy and must
be regenerated whenever a migration changes the schema:

```bash
mysqldump -u root --no-data --skip-comments --skip-dump-date \
  --ignore-table=repawter.schema_migrations repawter > db/schema.sql
```

The two are verified to produce byte-identical table definitions. If you change
one without the other, the next person to set up the project gets a different
database than you have.

---

## The `schema_migrations` table

Created automatically on first run.

| Column | Meaning |
|---|---|
| `version` | Leading number of the filename (`0003`). Primary key. |
| `filename` | Full filename, for readable status output. |
| `checksum` | SHA-256 of the file as applied; drives drift detection. |
| `applied_at` | When it ran. |
| `duration_ms` | How long it took — useful for spotting a migration that will lock a large table in production. |

`--status` also flags an `ORPHAN`: a row in `schema_migrations` with no matching
file. That normally means you have checked out a branch that predates a
migration this database already has.

---

## Failure behaviour

MySQL does not roll back DDL. If a migration fails part-way through, the
statements before the failure have already taken effect and the migration is
*not* recorded as applied. The runner says so explicitly. Inspect the database,
undo the partial change by hand, fix the file, then re-run — the
`IF NOT EXISTS` guards mean the statements that already succeeded are skipped.

This is the reason to keep migrations small and single-purpose.
