# Student Information System (PHP + MySQL)

Admin web app for managing departments, courses, students and their academic history.

**Default login:** `admin` / `admin123` — change it right away (sidebar → *Change Password*).

## Deploy on InfinityFree (step by step)

1. **Sign up** at infinityfree.com → *Create Account* → pick a free subdomain (e.g. `mysis.infinityfreeapp.com`).
2. **Create the database:** Client Area → your hosting account → *MySQL Databases* → create one (e.g. `sis`).
   Write down the four values shown: **host** (`sqlXXX.infinityfree.com`), **database name** (`if0_XXXXXXXX_sis`), **username** (`if0_XXXXXXXX`), **password**.
3. **Import the tables:** open *phpMyAdmin* for that database → *Import* tab → choose `schema.sql` → *Go*.
4. **Edit `config/config.php`** and replace the `sqlXXX…`, `if0_XXXXXXXX…` and password values with yours.
5. **Upload the files:** *Online File Manager* (or FTP) → open the `htdocs` folder → upload **the contents** of this project
   (so `index.php` sits directly inside `htdocs/`). Delete InfinityFree's default `index2.html` if present.
   Make sure the `logs/` folder exists and is writable (default permissions normally work).
6. **Open** `http://your-subdomain.infinityfreeapp.com/` and log in.

Other hosts (any cPanel host, Hostinger, etc.): same steps — create a MySQL DB, import `schema.sql`, edit `config/config.php`, upload.

### Local testing
`php -S localhost:8000` inside the project folder (needs PHP 8.1+ with `pdo_mysql` and a MySQL/MariaDB database).

### InfinityFree notes
- Free hosting sometimes shows a "browser check" page (a cookie/JavaScript test) the first time — let it finish in a normal browser.
- Keep `SIS_ENV` at `production` (default) so error details are never shown to visitors.

## Features
Secure login/logout · dashboard summary · department CRUD · course CRUD · student CRUD · academic history
(add/edit/delete) · update student status · delete student with all records · search, filter, pagination · print student record.

## Architecture (how the grading categories are covered)

| Area | Implementation |
|---|---|
| **Architecture & scalability** | MVC + OOP. `index.php` is a front controller with a route whitelist; `app/controllers`, `app/models`, `app/views`, `app/core`. **Stateless web tier:** sessions are stored in the database (`DbSessionHandler`), nothing is stored on the local disk except optional log files, and config can come from environment variables, so several app servers can run behind a load balancer. |
| **Security & identity** | 100% prepared statements (PDO, native prepares; column names come from per-model whitelists). `password_hash()` / `password_verify()` (bcrypt) with automatic rehash. Session ID regenerated on login, HttpOnly + SameSite cookies, strict mode, idle timeout. CSRF token on every POST. Output escaping (`e()`) everywhere. Server-side validation (`Validator`). Role-based access: only `admin` may delete departments, courses and students. Brute-force protection: 5 failed logins lock the account for 15 min. Generic login error (no user enumeration). Security headers; `app/`, `config/`, `logs/` blocked in `.htaccess`. |
| **Data management & portability** | All DB settings in one file (`config/config.php`, overridable by env vars). One `Database` class (PDO) used by every model; `Model` base class for shared CRUD. `schema.sql` creates everything incl. foreign keys, unique keys, indexes and the default admin. |
| **Maintainability & DevOps** | Commented, consistent naming, small single-purpose classes, views contain only presentation. `try/catch` in the session handler, global exception handler, friendly 403/404/405/419/500 pages. Config via environment variables. |
| **Observability & logging** | `Logger` writes structured JSON lines to `logs/app-YYYY-MM-DD.log` (logins, failures, lockouts, CSRF failures, every create/update/delete, exceptions with file/line). Users see friendly messages and flash alerts; technical details go only to the log. Falls back to the server `error_log` if `logs/` is not writable. |

## Folder structure
```
index.php               front controller / router
config/config.php       database + app settings (edit this)
schema.sql              database structure + default data
app/core/               Database, Config, Logger, Auth, Csrf, Validator, Model, Controller, session handler
app/models/             User, Department, Course, Student, Academic
app/controllers/        Auth, Dashboard, Department, Course, Student, Academic
app/views/              layouts, partials and screens
assets/css/style.css    styles (Bootstrap 5 + Icons are loaded from a CDN)
logs/                   application logs (not web-accessible)
```
