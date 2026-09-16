# TidyPOS — Laundry Point of Sale System

A full-featured POS system built for laundry businesses, featuring an **offline-capable PWA** for unreliable internet environments and a **Livewire-powered back-office** for administration.

## Architecture

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Back-Office | Laravel 12 + Livewire 3 | Admin dashboard, reports, settings, staff management |
| Offline POS | Vue 3 + Pinia + Dexie.js (PWA) | Cashier-facing POS that works fully offline |
| API | Laravel Sanctum + REST | Sync bridge between offline POS and server |
| Database | MySQL (prod) / SQLite (dev) | Relational data storage |

## Requirements

- PHP >= 8.4.1
- Composer
- Node.js 20 (CI baseline; Node.js >= 18 is supported)
- MySQL 8.0+ (production) or SQLite (development)

## Installation

1. Clone the repository
2. `composer install`
3. `npm install && npm run build`
4. `cp .env.example .env && php artisan key:generate`
5. Configure your database in `.env`
6. `php artisan migrate --seed`
7. Visit `/install` to complete setup

## Development

Use PHP 8.4.1 or newer for every Composer, Artisan, and test command. On Windows with Laravel Herd, prefix PHP and Composer commands with `herd` if plain `php` points to an older installation.

```shell README.md
# Start Laravel and Vite development servers
php artisan serve
npm run dev

# Run the isolated PHP tests
php artisan test

# Run the code formatter
./vendor/bin/pint
```

## Repeatable baseline

The baseline fails immediately on PHP versions older than 8.4.1 and runs platform checks, isolated PHP tests, route and migration checks, the production build, Playwright, and dependency audits. Database-aware checks are forced to `database/testing.sqlite`; Playwright uses the separately reset `database/e2e.sqlite`.

On Windows with Herd:

```powershell README.md
herd composer baseline
```

On systems where PHP 8.4 is the default runtime, including CI:

```shell README.md
composer baseline
```

Do not run the baseline through a Composer executable attached to an older PHP runtime. GitHub Actions enforces PHP 8.4 and Node.js 20 through `.github/workflows/baseline.yml`.

## Documentation

- [Offline POS Architecture](./Offline-POS-Documentation.md) — Detailed technical documentation for the PWA sync system.
