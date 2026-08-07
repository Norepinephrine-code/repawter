# 🚀 RePawter — Production Deployment Guide

This guide covers everything needed to move RePawter from a local XAMPP setup to a production server.

---

## 1. Prerequisites

| Requirement | Details |
|---|---|
| **PHP** | 8.2+ with `pdo_mysql`, `mbstring`, `fileinfo`, `gd`/`imagick` |
| **Web server** | Apache 2.4+ with `mod_rewrite`, `mod_headers`, `mod_ssl` (or Nginx + PHP-FPM) |
| **Database** | MySQL 8.0+ or MariaDB 10.4+ (InnoDB, utf8mb4) |
| **TLS** | A valid SSL/TLS certificate (Let's Encrypt is free) |
| **OS** | Any Linux distribution supported by your stack (Ubuntu 22.04/24.04 LTS recommended) |

---

## 2. Server Preparation

### 2.1 Install the LAMP stack (Ubuntu example)

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php8.2 php8.2-mysql php8.2-mbstring php8.2-gd php8.2-xml
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

### 2.2 Secure MySQL

```bash
sudo mysql_secure_installation
```

Follow the prompts to set a root password, remove anonymous users, disallow remote root login, and drop the test database.

### 2.3 Create the database and a least-privilege user

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE repawter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'repawter_app'@'localhost' IDENTIFIED BY 'use-a-long-random-string';
GRANT SELECT, INSERT, UPDATE, DELETE ON repawter.* TO 'repawter_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **Principle of least privilege:** The app user only needs `SELECT`, `INSERT`, `UPDATE`, and `DELETE`. It does **not** need `CREATE`, `ALTER`, `DROP`, or `GRANT`.

---

## 3. Deploy the Code

### 3.1 Clone or copy the project

```bash
sudo mkdir -p /var/www/repawter
sudo chown -R $USER:$USER /var/www/repawter
git clone https://github.com/<your-user>/repawter.git /var/www/repawter
```

### 3.2 Import the database schema

```bash
mysql -u repawter_app -p repawter < /var/www/repawter/db/schema.sql
```

> **Do NOT import `db/seed.sql` in production.** The seed file contains demo accounts with a known password (`Password123!`). If you need a system admin account, create one manually via SQL with a unique, strong password (see §4 below).

### 3.3 Set directory permissions

```bash
# Web server owns the tree
sudo chown -R www-data:www-data /var/www/repawter

# Directories that need write access
sudo chmod -R 775 /var/www/repawter/uploads
sudo chmod -R 775 /var/www/repawter/storage

# Everything else: read-only
sudo find /var/www/repawter -type f -not -path "*/uploads/*" -not -path "*/storage/*" -exec chmod 644 {} \;
sudo find /var/www/repawter -type d -not -path "*/uploads/*" -not -path "*/storage/*" -exec chmod 755 {} \;
```

---

## 4. Configure the Application

RePawter reads configuration from **environment variables** with safe fallbacks.
Every variable, its default, and what breaks if it is wrong is documented in
**[CONFIGURATION.md](CONFIGURATION.md)** — read that first; this section covers
only *where* to put the values on a server.

Choose **one** of the following methods:

### Method A — Apache VirtualHost `SetEnv` (recommended)

```apache
<VirtualHost *:443>
    ServerName repawter.example.org
    DocumentRoot /var/www/repawter

    # TLS
    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/repawter.example.org/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/repawter.example.org/privkey.pem

    # Application environment
    SetEnv APP_ENV prod
    SetEnv DB_NAME repawter
    SetEnv DB_USER repawter_app
    SetEnv DB_PASS "use-a-long-random-string"
    SetEnv BASE_URL ""           # empty string = served at domain root

    <Directory /var/www/repawter>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Method B — `config.local.php` (git-ignored)

Create `/var/www/repawter/app/config/config.local.php`:

```php
<?php
declare(strict_types=1);

define('APP_ENV', 'prod');
define('DB_USER', 'repawter_app');
define('DB_PASS', 'use-a-long-random-string');
define('BASE_URL', '');           // served at domain root
```

### Method C — Environment variables in PHP-FPM pool (Nginx)

In your pool config (`/etc/php/8.2/fpm/pool.d/repawter.conf`):

```ini
env[APP_ENV] = prod
env[DB_NAME] = repawter
env[DB_USER] = repawter_app
env[DB_PASS] = use-a-long-random-string
env[BASE_URL] = /
```

---

## 5. Create the Initial Admin Account

Since `seed.sql` is not imported in production, create the first admin manually:

```sql
-- Run in MySQL
INSERT INTO users (role, account_status, email, password_hash, full_name, created_at)
VALUES (
    'system_admin',
    'active',
    'admin@your-domain.com',
    '$2y$10$REPLACE_WITH_A_REAL_BCRYPT_HASH',
    'System Admin',
    NOW()
);
```

Generate the bcrypt hash with PHP:

```bash
php -r "echo password_hash('YourStrongPassword!', PASSWORD_DEFAULT);"
```

---

## 6. Enable HTTPS

### 6.1 Obtain a certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d repawter.example.org
```

### 6.2 Enable the HTTPS redirect

Edit `/var/www/repawter/.htaccess` and **uncomment** the rewrite block:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

---

## 7. Post-Deployment Checklist

| ✓ | Item |
|---|---|
| ☐ | `APP_ENV` is set to `prod` |
| ☐ | DB user is **not** `root` and has a strong password |
| ☐ | `db/seed.sql` was **not** imported |
| ☐ | `php db/migrate.php --status` shows every migration applied, none `CHANGED` or `ORPHAN` |
| ☐ | `curl -I https://your-host/.env` returns 403 or 404, not 200 |
| ☐ | `curl -I https://your-host/app/config/config.php` returns 403 or 404 |
| ☐ | `curl -I https://your-host/db/schema.sql` returns 403 or 404 |
| ☐ | `assets/vendor/` was deployed (the UI has no CDN fallback — a missing vendor directory renders every page unstyled) |
| ☐ | `storage/logs/` is writable by the web server |
| ☐ | `uploads/` is writable by the web server |
| ☐ | HTTPS is enforced (301 redirect) |
| ☐ | HSTS header is present (`Strict-Transport-Security`) |
| ☐ | Security headers are present (check with [securityheaders.com](https://securityheaders.com)) |
| ☐ | `display_errors` is `Off` (errors go to `storage/logs/php-error.log`) |
| ☐ | First admin account created with a unique, strong password |
| ☐ | All demo accounts from `seed.sql` are absent — `SELECT COUNT(*) FROM users WHERE email LIKE '%@example.test' OR email LIKE '%@repawter.test'` returns 0 |
| ☐ | An invalid URL returns the styled 404 page, and a permission denial returns a styled 403 — not a blank page |
| ☐ | Logging in and out works over HTTPS (if the session cookie never sticks, see `SESSION_SECURE` in [CONFIGURATION.md](CONFIGURATION.md)) |

---

## 8. Maintenance

### 8.1 View error logs

```bash
sudo tail -f /var/www/repawter/storage/logs/php-error.log
```

### 8.2 Backup the database

```bash
mysqldump -u repawter_app -p repawter | gzip > repawter-backup-$(date +%F).sql.gz
```

Schedule this as a daily cron job.

### 8.3 Update the code

```bash
cd /var/www/repawter
git pull origin main
# Run any new schema migrations if db/schema.sql changed
sudo chown -R www-data:www-data uploads storage
```

---

## 9. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Blank page / 500 error | `display_errors` is off and a PHP error occurred | Check `storage/logs/php-error.log` |
| Session lost on every request | `SESSION_SECURE` is `true` but site is on HTTP | Set `APP_ENV=prod` **and** ensure HTTPS is active |
| Upload fails with permission error | `uploads/` not writable | `sudo chmod -R 775 uploads && sudo chown -R www-data:www-data uploads` |
| CSP blocks CDN styles/scripts | CSP `default-src` too restrictive | Verify `cdn.jsdelivr.net` is in `script-src` and `style-src` |
| "Access denied" for DB | Wrong credentials or user lacks privileges | Verify `DB_USER`/`DB_PASS` and `GRANT` statements |