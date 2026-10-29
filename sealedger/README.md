# SeaLedger

Fish vendor inventory and sales management system built with Laravel, Tailwind CSS and jQuery.

## Setup

1. Start Apache and MySQL in XAMPP, then create an empty database named `sealedger` in phpMyAdmin.
2. Run these in the project folder:

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

For development use `npm run dev` in a second terminal instead of `npm run build`.

## Accounts

| Persona | Access | Login |
|---|---|---|
| Vendor | `/login` then `/admin/dashboard` | username `vendor`, password `SeaLedger123!` |
| Customer | `/` (no login, view only) | none |

## Mobile API (Android app)

`routes/api.php` exposes a JSON API under `/api` for the SeaLedger Android app. It uses the same models and
database as the website, with bearer-token login (`api_tokens` table, `POST /api/login`).

Local setup, no deployment needed:

```bash
php artisan migrate
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000   # reachable from a phone on the same Wi-Fi
```

See the Android project's README for the app side.
