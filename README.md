

# Hybrid Database Support Backend for Healthcare Operations

A backend application demonstrating hybrid relational and NoSQL database usage
in a healthcare support context.

This project is based on a CS306 Phase 3 assignment and focuses on **database
design, backend logic, and system integration**, rather than a full-scale
healthcare product.

## Project structure
- `sql/schema.sql` – MySQL/MariaDB dump (DB + tables + stored procedures + triggers)
- `htdocs/` – PHP pages
  - `user/` – user support ticket UI (MongoDB)
  - `admin/` – admin support ticket UI (MongoDB)
  - `mysql_tests/` – demo pages for procedures & triggers (MySQL)

---

## 1) MySQL setup (XAMPP / phpMyAdmin)
1. Start **Apache** + **MySQL**.
2. Open phpMyAdmin → **Import** → select `sql/schema.sql`.
   - Creates DB `cs306` and loads procedures/triggers.
3. (Optional) Visit `htdocs/mysql_tests/test_select.php` to verify a basic SELECT works.

### MySQL connection
`htdocs/mysql_tests/db.php` uses XAMPP defaults:
- host: `localhost`
- user: `root`
- pass: *(empty)*
- db: `cs306`

If your setup differs, update that file.

---

## 2) MongoDB setup (Support tickets)
The support ticket system uses MongoDB (tickets stored in a collection).

### Requirements
- MongoDB server running (default `mongodb://127.0.0.1:27017`)
- PHP MongoDB extension enabled
- Composer dependency: `mongodb/mongodb`

### Install dependencies
From the repo root:
```bash
composer install
```

### Configure Mongo
Copy the example env file:
```bash
cp .env.example .env
```
Set env vars in your web server / shell:
- `MONGO_URI`
- `MONGO_DB`
- `MONGO_COLLECTION`

The connection code lives in `htdocs/mongo.php`.

---

## 3) Running the project locally
Place the project folder under your web server root.
Examples:
- macOS XAMPP: `xamppfiles/htdocs/`
- Windows XAMPP: `C:/xampp/htdocs/`

Then open:
- User panel: `http://localhost/hastanep/htdocs/user/`
- Admin panel: `http://localhost/hastanep/htdocs/admin/`
- MySQL demo pages: `http://localhost/hastanep/htdocs/mysql_tests/`

---

## Pages
### User (MongoDB)
- `user/support_index.php` – list tickets by username (active/resolved/all)
- `user/ticket_create.php` – create a new ticket
- `user/ticket_detail.php` – read-only ticket detail

### Admin (MongoDB)
- `admin/index.php` – list active tickets
- `admin/ticket_detail.php` – add admin comment / mark resolved

### MySQL procedures & triggers
- `mysql_tests/index.php` – index page for all demos
- Procedures: `proc_1.php` … `proc_4.php`
- Triggers: `trigger_1.php` … `trigger_4.php`

---
## Quick start (Docker Compose)

Prerequisite: Docker Desktop installed.

```bash
cp .env.example .env   # optional, compose already includes sane defaults
docker compose up --build
```

Then open: http://localhost:8080

- User ticket UI: http://localhost:8080/user
- Admin UI: http://localhost:8080/admin
- MySQL demo pages: http://localhost:8080/mysql_tests

---


## Notes
- `.env` is ignored via `.gitignore` (do not commit secrets).
- If you see errors about MongoDB classes, check: (1) PHP extension, (2) `composer install`, (3) env vars.
