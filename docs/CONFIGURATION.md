# Configuration reference

Every setting RePawter reads, where it comes from, and what happens if you get
it wrong.

The template to copy is [`.env.example`](../.env.example). The code that reads
these values is [`app/config/config.php`](../app/config/config.php).

---

## How a setting is resolved

Four layers, most specific first. The first one that supplies a value wins.

| Layer | Set by | Use it for |
|---|---|---|
| 1. `config.local.php` | `define('DB_PASS', '…')` in `app/config/config.local.php` | A local override you never want in version control. Git-ignored. |
| 2. Real environment variable | Apache `SetEnv`, php-fpm `env[]`, systemd, the shell | **Production.** |
| 3. `.env` file | `KEY=value` lines at the project root | Local development and CI. Git-ignored. |
| 4. Built-in default | `app/config/config.php` | Getting started with zero configuration. |

A real environment variable always beats `.env`, so a file left behind on a
server can never quietly override the server's own configuration.

> `.env` and `config.local.php` are both git-ignored, and the root `.htaccess`
> blocks every dotfile from web access. Verify after deploying:
> `curl -I https://your-host/.env` must return 403 or 404, never 200.

---

## Application

### `APP_NAME`
Display name in the browser title and page furniture.
Default `RePawter`. Optional.

### `APP_ENV` — **required in production**
Either `dev` or `prod`. This one variable switches several behaviours at once:

| | `dev` | `prod` |
|---|---|---|
| PHP errors | shown on screen, with stack trace | logged to `storage/logs/php-error.log`, never displayed |
| Error page | shows the exception | shows only "Something went wrong" |
| Session cookie `Secure` flag | off | on (unless `SESSION_SECURE` says otherwise) |
| `php db/migrate.php --seed` | allowed | refused |

Default `dev`. **Leaving this at `dev` in production leaks file paths, SQL and
stack traces to visitors.**

### `BASE_URL`
URL path the app is served under — leading slash, no trailing slash. Every
internal link and asset URL is built from it (`url()` and `asset()` in
`app/core/view.php`).

| Deployment | Value |
|---|---|
| XAMPP at `htdocs/repawter` | `/repawter` (the default) |
| Domain root, e.g. `https://repawter.example.gov.ph` | *empty string* |
| Sub-path, e.g. `…/animals` | `/animals` |

An empty string is a meaningful value here and is preserved — do not replace it
with `/`. Get this wrong and every link 404s while the site itself loads fine.

### `APP_TZ`
PHP timezone identifier, e.g. `Asia/Manila` (the default). Timestamps are stored
and displayed in this zone; `app/core/db.php` also pins the MySQL session
timezone to `+08:00`. If you deploy outside the Philippines, change both.

---

## Database

All four are read at connection time in `app/core/db.php`.

| Variable | Default | Notes |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | Prefer `127.0.0.1` over `localhost`: the latter makes MySQL use a Unix socket, which often fails under a hardened `open_basedir`. |
| `DB_NAME` | `repawter` | Must already exist — nothing creates it for you. |
| `DB_USER` | `root` | **Never `root` in production.** |
| `DB_PASS` | *(empty)* | Empty is valid, and is the XAMPP default. |
| `DB_CHARSET` | `utf8mb4` | Do not change; the schema is `utf8mb4_unicode_ci` throughout. |

The runtime account needs only four privileges:

```sql
CREATE USER 'repawter_app'@'localhost' IDENTIFIED BY 'a-long-random-string';
GRANT SELECT, INSERT, UPDATE, DELETE ON repawter.* TO 'repawter_app'@'localhost';
FLUSH PRIVILEGES;
```

No `DROP`, no `ALTER`, no `CREATE`. Schema changes go through the migration
runner, which can use a different account:

| Variable | Default | Notes |
|---|---|---|
| `DB_MIGRATION_USER` | falls back to `DB_USER` | Read only by `db/migrate.php`. Needs DDL rights. |
| `DB_MIGRATION_PASS` | falls back to `DB_PASS` | |

See [`db/migrations/README.md`](../db/migrations/README.md).

---

## Uploads

### `MAX_UPLOAD_BYTES`
Largest accepted image, in bytes. Default `5242880` (5 MiB). Enforced in
`app/core/upload.php` alongside a MIME-type and `getimagesize()` check.

PHP's own limits must be at least as large, or the upload is discarded before
the app ever sees it:

```ini
upload_max_filesize = 6M
post_max_size       = 8M
```

Accepted image types (`image/jpeg`, `image/png`, `image/webp`) are a code
constant, not an environment variable — widening them is a security decision,
not a deployment one.

---

## Security

### `SESSION_SECURE`
Marks the session cookie `Secure`, so browsers send it only over HTTPS.
Defaults to `true` when `APP_ENV=prod`, `false` otherwise.

Override it only for a staging box running production config over plain HTTP.
Leaving it on without HTTPS means the browser never stores the cookie and
**nobody can log in** — the login form simply bounces back with no error.

The other session hardening (`HttpOnly`, `SameSite=Lax`, `use_strict_mode`) is
always on and is not configurable.

### `TRUST_PROXY`
Default `false`. When `true`, `X-Forwarded-Proto: https` is accepted as proof
the request arrived over TLS, which is how HSTS gets sent when TLS terminates
at a load balancer.

Enable this **only** when a reverse proxy you control sits in front of the app
and strips inbound `X-Forwarded-*` headers. With no proxy, any visitor can send
that header themselves.

---

## Not configurable by design

Some things deliberately have no environment variable, because changing them is
a code review, not a deploy-time toggle:

- **Allowed upload MIME types** — widening them changes the security model.
- **The Content-Security-Policy** — set in both `app/bootstrap.php` and
  `.htaccess`; edit those together if you add a CDN.
- **Role definitions and the permission map** — `app/core/rbac.php`.
- **Verification criteria** — these *are* runtime-editable, but through the
  admin UI at `/admin/system/criteria.php` and the database, not config.

---

## Production checklist

| ☐ | Check |
|---|---|
| ☐ | `APP_ENV=prod` |
| ☐ | `BASE_URL` matches how the site is actually served (empty for domain root) |
| ☐ | `DB_USER` is not `root`, has a long random password, and holds only SELECT/INSERT/UPDATE/DELETE |
| ☐ | `curl -I https://host/.env` returns 403 or 404 |
| ☐ | `storage/logs/` exists and is writable by the web server user |
| ☐ | `uploads/` is writable, and PHP execution is blocked inside it |
| ☐ | HTTPS is live; `TRUST_PROXY=true` only if terminating TLS upstream |
| ☐ | `db/seed.sql` was **not** loaded (`SELECT COUNT(*) FROM users WHERE email LIKE '%@example.test'` returns 0) |
| ☐ | `php db/migrate.php --status` shows every migration applied and none `CHANGED` |

Full server setup — TLS, Apache vhost, file permissions — is in
[`DEPLOYMENT.md`](DEPLOYMENT.md).

---

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Every link 404s but the home page loads | `BASE_URL` does not match the real path |
| Login form reloads with no error, forever | `SESSION_SECURE=true` on a plain-HTTP site |
| Blank page, HTTP 500 | Check `storage/logs/php-error.log`; in `dev` the page shows the exception |
| "Access denied for user" | Wrong `DB_USER`/`DB_PASS`, or the grant was never run |
| "Access denied" only when running migrations | The migration account lacks DDL rights — set `DB_MIGRATION_USER` |
| Timestamps off by several hours | `APP_TZ` disagrees with the `SET time_zone` in `app/core/db.php` |
| CDN styles or scripts blocked | A new CDN host needs adding to the CSP in *both* `app/bootstrap.php` and `.htaccess` |
