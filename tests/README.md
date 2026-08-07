# Tests

Two suites, with different jobs:

| Suite | Runner | What it covers | Needs |
|---|---|---|---|
| `tests/unit` | Jest + jsdom | The browser-side JavaScript in `assets/js`, in isolation | Node only |
| `tests/e2e` | Playwright + Chromium | The whole application through a real browser | PHP, MySQL/MariaDB |

```bash
npm install          # once

npm run test:unit    # fast, no server or database
npm run test:e2e     # boots PHP and resets the test database itself
npm test             # both
```

---

## Unit tests (Jest)

`assets/js/app.js` and `assets/js/calendar-init.js` are plain `<script>` files —
this project has no build step — so they are written in the UMD style: under
CommonJS they export their functions and start nothing, and in a browser they
attach to `window` and initialise themselves. That is what makes them loadable
here.

Collaborators are passed in rather than reached for (`init(doc, {window,
confirm, FileReader})`), so the tests need no browser and no timers they cannot
control.

```bash
npm run test:unit
npm run test:coverage     # enforces the thresholds in jest.config.js
```

Two things worth knowing if you add tests:

- **`init()` registers document-level listeners and never removes them.** Tests
  that call it use `freshDoc()` to get an isolated `Document`, or a declining
  handler from one test suppresses events in the next.
- **jsdom does not implement form submission.** Assert on `handleConfirmSubmit`
  directly for the "user accepts" path; a dispatched, un-prevented `submit`
  event raises "Not implemented" instead of doing anything useful.

---

## End-to-end tests (Playwright)

These drive a real browser against a real PHP server and a real database. They
are the only place the PHP application is executed, so they carry the weight of
verifying it.

### What you need

- PHP 8.2+ with `pdo_mysql` (`php -v`)
- MySQL or MariaDB reachable, and a database to throw away
- The Chromium build Playwright expects (`npx playwright install chromium`)

Create the test database once:

```sql
CREATE DATABASE repawter_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then, if your database credentials are not the defaults:

```bash
cp .env.example .env      # set DB_USER / DB_PASS
```

### Running

```bash
npm run test:e2e                       # everything
npx playwright test smoke.spec.js      # one file
npx playwright test --project=mobile   # the @mobile specs only
npx playwright test --ui               # interactive
npm run test:e2e:report                # open the last HTML report
```

The suite starts and stops its own web server (`playwright.config.js` →
`webServer`), so nothing needs to be running first.

### How it is wired

**The server.** `tests/support/router.php` runs under PHP's built-in server and
reproduces the `/repawter` sub-path a normal Apache deployment serves from —
stripping the prefix, serving `index.php` for directory URLs, and returning the
project's own 404 page. It is test tooling and must never be deployed.

**The database.** `tests/support/global-setup.js` runs
`tests/support/reset-db.php` once before the run, which drops every table,
re-applies the migrations in `db/migrations/`, and loads `db/seed.sql`. Using
the real migration runner means each test run also exercises the path an
operator takes to set up a server.

`reset-db.php` refuses to run when `APP_ENV=prod`, and refuses to touch a
database whose name contains neither "test" nor "e2e" unless
`ALLOW_UNSAFE_DB_RESET=1` is set.

**Serialisation.** `workers: 1` and several `test.describe.configure({ mode:
'serial' })` blocks. The specs share one database and deliberately drive state
through a workflow — a report is filed, then reviewed, then verified, then
assigned — so they must not run concurrently.

### Writing specs

**Paths are relative to `baseURL`, never absolute.** `baseURL` is
`http://127.0.0.1:8100/repawter/`; Playwright resolves with `new URL(path,
baseURL)`, so a leading slash replaces the whole path and drops the `/repawter`
prefix. The session cookie is scoped to that prefix, so the symptom is a
mysteriously logged-out page rather than a 404.

```js
await page.goto('auth/login.php');    // correct
await page.goto('/auth/login.php');   // silently loses the session
```

**Use the helpers.** `tests/e2e/helpers.js` provides `login()`, `logout()`,
`unique()` (collision-proof test data), `expectNoHorizontalOverflow()`,
`expectNoPhpErrors()` and `collectPageProblems()`. Anything that encodes what a
*feature* should do belongs in the spec, where it is readable.

**Add new pages to `smoke.spec.js`.** Its route lists are meant to be
exhaustive: every page must be known to render without a 500, a PHP notice, a
missing asset or a sideways scrollbar.

### The files

| Spec | Covers |
|---|---|
| `smoke.spec.js` | Every route renders, for every role. The suite's floor. |
| `auth.spec.js` | Registration, login, sessions, suspended accounts, profile |
| `reports.spec.js` | Filing → checklist → verify → assign → resolve → archive |
| `applications.spec.js` | Foster and adoption applications, PDF agreements |
| `announcements.spec.js` | Calendar, feed, publishing, resources, analytics |
| `rbac.spec.js` | The full role × page permission matrix |
| `security.spec.js` | CSRF, escaping, injection, uploads, headers, fixation |
| `accessibility.spec.js` | Landmarks, labels, keyboard access, reduced motion |

`rbac.spec.js` restates the permission map from `app/core/rbac.php`
independently, on purpose: a matrix that only agrees with itself proves nothing,
so changing one without the other has to fail the build.

---

## Configuration

Both suites read the variables documented in
[`docs/CONFIGURATION.md`](../docs/CONFIGURATION.md). The E2E suite adds a few of
its own, all optional:

| Variable | Default | Purpose |
|---|---|---|
| `E2E_DB_NAME` | `repawter_e2e` | Database the suite resets. Must be disposable. |
| `E2E_HOST` | `127.0.0.1` | Address the test server binds to |
| `E2E_PORT` | `8100` | Port the test server binds to |
| `E2E_BASE_URL` | derived | Override the whole origin, to test against another server |
| `ALLOW_UNSAFE_DB_RESET` | unset | Bypass the "test"/"e2e" name check in `reset-db.php` |

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| `E2E database reset failed` | The database does not exist, or the credentials are wrong. See the setup step above. |
| `Executable doesn't exist at …chrome-headless-shell` | The installed `@playwright/test` and the downloaded browser are different versions. Run `npx playwright install chromium`. |
| A test is logged out unexpectedly | An absolute path (leading `/`) in `goto` — see "Writing specs". |
| Everything passes locally, one thing fails in CI | Check for state left behind by a previous run; the database is only reset between runs, not between tests. |
| `port 8100 is already in use` | A previous run's server survived: `pkill -f "php -S 127.0.0.1:8100"` |

---

## What is not covered here

- **The `.htaccess` rules** that block `/app`, `/db`, `/lib` and `/storage` are
  enforced by Apache. The built-in server used by the suite does not read
  `.htaccess`, and having the test router imitate them would only be testing
  the router. Verify them against a real deployment:
  `curl -I https://your-host/app/config/config.php` must not return 200.
- **Email delivery.** Out of scope by design; "sent" mail is written to the
  `email_outbox` table.
- **Load and performance.** Nothing here says anything about behaviour under
  concurrency.
