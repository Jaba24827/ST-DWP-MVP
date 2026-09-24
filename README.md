# SL-DWP — full-stack MVP package

St. Luke Foundation Digital Workplace Platform. Laravel 11 backend (Blade + a
parallel React presentation tier), MySQL 8 database, MFA, and a shared
password policy enforced identically on the server and in the browser.

## Contents

```
database/schema/sldwp_schema.sql   Hand-written reference schema + seed data
database/migrations/               The same schema, as Laravel migrations
database/seeders/                  RbacSeeder (roles/permissions), DemoDataSeeder
app/Models/                        Eloquent models — one per table
app/Http/Controllers/              Auth/, plus one controller per resource
app/Http/Middleware/               EnsurePermission, EnsureMfaVerified, SecurityHeaders
app/Http/Requests/                 Validation — the input boundary
app/Services/                      AuditLogger, MfaService, PasswordPolicy, DocumentService
app/Rules/StrongPassword.php       The one password-policy expression
app/Notifications/                 MfaCodeNotification (email/SMS)
resources/views/                   Blade screens (server-rendered reference UI)
resources/css/sldwp.css            Design system: green brand, white sidebar, dark mode
resources/js/                      React presentation tier (calls routes/api.php)
public/assets/                     Logo SVGs, jQuery progressive-enhancement script
routes/web.php, routes/api.php     Every route pinned to auth + mfa + perm middleware
tests/Feature/AuthorizationTest.php  Pest tests — mostly the denial paths
docs/DEVELOPER_HANDBOOK.md         Full build/run/deploy/security reference
```

## Fast path

```bash
composer create-project laravel/laravel sldwp && cd sldwp
composer require laravel/breeze laravel/sanctum --dev
php artisan breeze:install blade
# copy this package's app/, database/, resources/, routes/, public/assets,
# config/sldwp.php and .env.example over the generated project

cp .env.example .env && php artisan key:generate
mysql -u root -p -e "CREATE DATABASE sldwp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\RbacSeeder
php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder

npm install && npm run build     # or: npm run dev
php artisan serve
```

Sign in with any seeded address and `Demo!Passw0rd2026` — see
`docs/DEVELOPER_HANDBOOK.md` for the full account list, the MFA flow in
development (codes are written to `storage/logs/laravel.log` when
`MAIL_MAILER=log`), and everything else a new developer needs before
touching the code.

Everything in this package uses synthetic data. No real St. Luke Foundation
records are represented anywhere.
