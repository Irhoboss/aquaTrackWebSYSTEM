# AquaTrack — IoT pH Monitoring and Inventory Management (Laravel)

For Philzen Water Refilling Station. This package is an **overlay**: it contains every application file (migrations, models, controllers, services, routes, Blade views, CSS/JS, seeders, ESP32 firmware, docs) and is copied on top of a fresh Laravel project.

> Not yet run against a live Laravel install by the author of this package (no PHP in the build environment). Follow the steps below; if `php artisan` reports an error, send me the message and I will fix the file.

## 1. Requirements
XAMPP (PHP 8.2+, MySQL/MariaDB, phpMyAdmin), Composer, VS Code, an ESP32 + PH-4502C + pH probe + buffer solutions (pH 4.01 / 6.86).

## 2. Install
```bash
# 1) create a fresh Laravel project (Laravel 11 or 12)
composer create-project laravel/laravel aquatrack
cd aquatrack

# 2) copy this overlay on top of it (answer "yes" to overwrite).
#    It replaces: bootstrap/app.php, routes/*.php, app/Models/User.php,
#    app/Providers/AppServiceProvider.php, database/seeders/DatabaseSeeder.php,
#    and the default users migration (0001_01_01_000000_create_users_table.php).

# 3) create the database in phpMyAdmin:  aquatrack  (utf8mb4_unicode_ci), then edit .env:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=aquatrack
#    DB_USERNAME=root
#    DB_PASSWORD=
#    APP_TIMEZONE stays UTC (storage). Display time is Asia/Manila: APP_DISPLAY_TZ=Asia/Manila
#    MAIL_MAILER=log        (password-reset links appear in storage/logs/laravel.log)
#    AQUATRACK_ADMIN_PASSWORD=ChangeMe!2026   (optional: choose your own temporary password)

php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0        # LAN access so the ESP32 can reach it
php artisan schedule:work               # second terminal: offline-sensor / stock / inspection alert checks
```
`migrate --seed` creates the owner account and prints the **ESP32 device token once** — copy it.
Delete Laravel's sample tests in `tests/` (they do not know about the login redirect).

Optional demo data (sample users, items, containers, 24 h of readings): `php artisan db:seed --class=DemoSeeder`.

**First login:** username `admin`, password `ChangeMe!2026` (or your `AQUATRACK_ADMIN_PASSWORD`). You are forced to change it immediately. Demo accounts (`manager`, `staff`) behave the same way.

## 3. Roles
Permissions live in **one file**: `config/permissions.php` (role → abilities). Routes use `can:<ability>` middleware and Blade uses `@can`, so the backend enforces it, not just the menu.

| Area | Owner | Manager | Staff |
|---|:-:|:-:|:-:|
| Dashboard | yes | yes | yes (no pH cards) |
| pH monitoring & history | yes | yes | no |
| Alerts: view / acknowledge+resolve | yes / yes | yes / yes | own types / no |
| Inventory view / manage / stock moves | yes | yes | view + stock moves |
| Suppliers | yes | yes | no |
| Containers view / manage / inspections | yes | yes | view + inspections |
| Reports | all | operational | no |
| Audit log | yes | yes | no |
| Users, settings, devices, negative-stock exception | yes | no | no |

## 4. Connect the ESP32
1. Read `docs/HARDWARE.md` (voltage divider is required: the module can output up to 5 V, the ESP32 ADC takes 3.3 V max).
2. Open `firmware/aquatrack_esp32.ino` in Arduino IDE (ESP32 board package). Set Wi-Fi, `API_URL` (your PC's LAN IP), `DEVICE_TOKEN`.
3. Calibrate with buffers and enter `CAL_MV_A` / `CAL_MV_B`.
4. Upload. Readings appear within a minute on **pH monitoring**. API reference: `docs/API.md`.

## 5. How the main rules work
* **pH status** from thresholds in *Settings* (defaults 6.5–8.5 are placeholders, **not official standards**). Missing/impossible values become *Invalid reading*; sensor faults become *Sensor error* — never "safe".
* **Alerts** are de-duplicated: while an alert is open, repeats only increase a counter. When the value returns to range (or stock is replenished, inspection done, device reports again) the alert is auto-resolved. `aquatrack:check` (scheduled every 5 min) raises offline-sensor, overdue-inspection and replacement alerts.
* **Inventory**: quantity only changes through stock movements, each written to a ledger inside a DB transaction with row locking. Negative stock is refused unless the owner ticks the exception. Items with history are archived, not deleted.
* **Containers**: inspections and maintenance are append-only history; retiring a container keeps everything.
* **Users**: nobody can change their own role/status; the last active owner cannot be demoted or deactivated; temporary passwords are shown once and never logged.
* **Audit log**: logins, failed logins, user/role changes, profile/password changes, inventory & stock, inspections, alerts, settings, devices. Passwords and tokens are never recorded.
* **Time**: stored in UTC, displayed in Asia/Manila.

## 6. File map
```
bootstrap/app.php            routes (web/api/console)       config/aquatrack.php, config/permissions.php
app/Models/*                 app/Http/Controllers/*         app/Http/Middleware/*
app/Services/*  (classifier, alerts, stock ledger, ingest, dashboard, reports)
app/Support/*   (Setting, Fmt, Audit, Csv)                  app/Console/Commands/CheckSystem.php
database/migrations/*        database/seeders/*             resources/views/**  public/css, public/js
firmware/aquatrack_esp32.ino docs/HARDWARE.md docs/API.md
```

## 7. Before real use
Change all temporary passwords, set `APP_ENV=production`, `APP_DEBUG=false`, use HTTPS between the ESP32 and server, configure real mail for password resets, back up the database, and confirm the pH limits and testing obligations with the proper regulator. Sensor readings do not certify water as potable.
