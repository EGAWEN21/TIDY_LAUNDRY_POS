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
- Node.js >= 18
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

```bash
# Start Laravel dev server
php artisan serve

# Start Vite dev server (for Vue PWA hot-reload)
npm run dev

# Run tests
php artisan test

# Run code formatter
./vendor/bin/pint
```

## Documentation

- [Offline POS Architecture](./Offline-POS-Documentation.md) — Detailed technical documentation for the PWA sync system.
