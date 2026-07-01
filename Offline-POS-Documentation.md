# TidyPOS Offline PWA Architecture & Documentation

## Overview
The TidyPOS Offline application is a Progressive Web App (PWA) built to ensure that staffs can create orders and add customers seamlessly, even when the internet connection (Wi-Fi or cellular) drops completely.

Once the connection is restored, the application automatically synchronizes all locally queued data securely to the main Laravel database.

## Architecture & Technology Stack

- **Backend (API)**: Laravel 11.x, serving endpoints via `PosApiController`.
- **Authentication**: Laravel Sanctum (API Tokens). This ensures that sessions do not expire if a device is offline for several days.
- **Frontend Framework**: Vue 3 (Composition API) integrated via `laravel-vite-plugin`.
- **State Management**: Pinia (`posStore.js`).
- **Offline Database**: Dexie.js (A robust wrapper for the browser's native IndexedDB).
- **Service Worker / Caching**: `vite-plugin-pwa` utilizing Google's Workbox.

## How It Works

### 1. Secure Initialization
When a logged-in Manager or Staff member clicks the **"Offline POS"** link in the admin dashboard, they are taken to `/admin/pos-app`.
1. The Blade template (`pos-app.blade.php`) automatically provisions a long-lived **Sanctum API Token** for the authenticated user.
2. It passes this token securely to the Vue application.
3. The Vue app initializes the `PosStore`. If the device is online, it fetches the entire product catalog, settings (including tax configurations), and customer list from `/api/pos/init`.
4. It saves all of this data locally into the browser using **Dexie.js**.

### 2. Going Offline
If the network drops, the application detects the `offline` event immediately.
The staff member can continue working exactly as normal:
- Browsing and searching products.
- Adding products to the cart.
- Adjusting quantities and colors.
- Creating **New Customers**.
- Checking out (Paying Cash or Saving).

### 3. The Offline Queue (Dexie.js)
When an order is completed offline, it is assigned a temporary cryptographic UUID. It is then saved into the `syncQueue` table inside IndexedDB, with a status of `pending`.

### 4. Background Synchronization
When the internet connection returns (the `online` event fires):
1. The Pinia store automatically triggers `syncOfflineData()`.
2. It gathers all pending customers and pending orders from the Dexie database.
3. It POSTs them to `/api/pos/sync-customers` and `/api/pos/sync-orders`.
4. The Laravel backend processes the UUIDs, inserts them into the MySQL database, generates real sequential Order IDs (e.g., `ORD-0042`), and applies the correct tax data.
5. The API returns a success mapping, and the local Dexie queue is cleared.

## Future Extensibility: The Manager Approval Workflow
Because the Offline POS is decoupled from the main database via an API, adding new features like an **Approval Workflow** is straightforward:
1. In `posStore.js`, change the default status of a synced order from `0` (Pending) to a new status (e.g., `99` - Awaiting Approval).
2. Create a new Livewire view in the Laravel backend for Managers to review orders with status `99`.
3. When a Manager rejects an order, they can add a note.
4. When the Offline POS syncs, it downloads these rejected orders, allowing the lower-level staff to modify the cart and resubmit.

## Development & Build Commands

If you make any changes to the Vue files located in `resources/js/vue/`, you must recompile the assets so the Service Worker is updated.

Run this command in the terminal from the root `tidypos` directory:

```bash
npm run build
```

This will generate the new bundles and the `public/build/sw.js` file automatically.
