# 🐾 RePawter

**Community-Based Stray Animal Management and Reporting System**

RePawter is a web platform that centralizes stray-animal reporting and foster/adoption initiatives to improve animal-welfare coordination between **community residents, barangay officials, and animal-welfare organizations**. Residents can report stray or injured animals, apply to foster, browse an adoption gallery, view an announcements calendar, and read rabies-prevention resources — while officials and shelters verify and track reports through to resolution, review applications, publish announcements, manage users, and generate PDF adoption agreements.

Built as an IT-Programming major project.

---

## ✨ Features

### Client side (Residents)
- **Registration & login** — email-based accounts with profile (name, barangay, contact).
- **Animal reporting** — submit a report with type, **one photo**, address/landmark, and urgency; track its verification status. Reports lock once under review.
- **Foster applications** — apply to foster with animal preferences and household capacity.
- **Adoption gallery** — browse adoptable pets and submit adoption applications.
- **Announcements calendar** — interactive (FullCalendar) view of vaccination drives, adoption events, and TNR schedules, with optional linked Facebook posts.
- **In-app notifications** — updates on reports, applications, and announcements (plus an email-outbox log).
- **Educational resources** — articles on responsible pet ownership and rabies prevention.
- **Report history** — view your own reports and applications.

### Server side (Barangay Officials / Welfare Orgs / System Admin)
- **Role-based access control** — 4 roles; staff/admin accounts are provisioned manually.
- **Report management** — filter, assign, and update reports; run an **admin-defined verification checklist**; archive (never hard-delete).
- **Case tracking** — follow an animal from report → resolution.
- **Foster & adoption review** — approve/reject applications; view applicant history.
- **Pet profile management** — publish public pet profiles and generate **PDF adoption agreements** (FPDF).
- **Announcement & schedule management** — publish to the shared calendar.
- **User management** — flag/suspend accounts.
- **Analytics** — report volume, response times, case outcomes; printable + CSV monthly summaries.
- **System admin** — edit verification criteria; view audit logs.

---

## 🛠 Tech stack

| Layer | Choice |
|---|---|
| Language | **PHP 8.2** (vanilla, PDO + prepared statements, no framework) |
| Database | **MySQL / MariaDB** (InnoDB, utf8mb4) |
| UI | **Bootstrap 5** + custom "paw" theme, **FullCalendar**, **Chart.js** (all via CDN) |
| PDF | **FPDF** (vendored, `lib/fpdf/`) |
| Server | **XAMPP** (Apache + MySQL) |

No Composer / npm / build step — clone, import the database, and run.

---

## 🚀 Setup (XAMPP on Windows)

1. **Install [XAMPP](https://www.apachefriends.org/)** (PHP 8.2). Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. **Get the code into `htdocs`** as a folder named exactly `repawter` (the app is served under `/repawter`):
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/<your-user>/repawter.git repawter
   ```
   > Prefer keeping the code elsewhere? Use a junction instead:
   > `mklink /J C:\xampp\htdocs\repawter D:\path\to\repawter`
3. **Create the database and import schema + seed data** (default XAMPP MySQL user is `root` with no password):
   ```bash
   C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE repawter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   C:\xampp\mysql\bin\mysql -u root repawter < db/schema.sql
   C:\xampp\mysql\bin\mysql -u root repawter < db/seed.sql
   ```
4. **Open the app:** <http://localhost/repawter/>

DB connection settings live in [`app/config/config.php`](app/config/config.php) — change `DB_USER`/`DB_PASS` there if your MySQL differs.

---

## 👤 Demo accounts

All seeded accounts share the password **`Password123!`**.

| Role | Email |
|---|---|
| System Admin | `admin@repawter.test` |
| Barangay Official | `official.brgy1@repawter.test` · `official.brgy2@repawter.test` |
| Welfare Org / Shelter | `shelter@pawrescue.test` · `org@strayhaven.test` |
| Resident | `juan.delacruz@example.test` · `maria.santos@example.test` |
| Resident (flagged) | `pedro.reyes@example.test` |

New residents can also self-register at `/auth/register.php`.

---

## 📁 Project structure

```
repawter/
├─ index.php  403.php  404.php        # entry + error pages
├─ auth/  reports/  foster/  adoption/  announcements/  notifications/  resources/
├─ admin/                             # role-guarded staff/admin area
│  ├─ dashboard.php  reports/  foster/  pets/  adoption/
│  └─ announcements/  users/  analytics/  resources/  system/
├─ actions/                           # POST-only handlers (CSRF-protected)
├─ app/
│  ├─ bootstrap.php  config/          # config + wiring
│  ├─ core/                           # db, session, auth, rbac, csrf, upload, notify, view, ...
│  ├─ models/                         # UserModel, ReportModel, CaseModel, PetModel, ...
│  └─ views/                          # layouts + partials
├─ assets/  uploads/  lib/fpdf/
├─ db/schema.sql  db/seed.sql
└─ docs/CONTRACTS.md                  # internal developer contract
```

## 🔐 Security notes
- All queries use **PDO prepared statements**; all output is escaped with `e()` (htmlspecialchars).
- **CSRF tokens** on every POST; **RBAC** guards every admin page; passwords are hashed with `password_hash()`.
- Uploads are validated by MIME + `getimagesize()`, stored with random names, and cannot execute as scripts.
- Private folders (`app/`, `db/`, `lib/`, `docs/`) are blocked from web access via `.htaccess`.

## ⚠️ Scope / limitations
Google Maps pins, SMS, payment gateways, and real email delivery are out of scope (future enhancements) per the project brief. Notifications are in-app; "sent" emails are logged to an `email_outbox` table.

---

*Made with 🐾 for safer, kinder communities.*
