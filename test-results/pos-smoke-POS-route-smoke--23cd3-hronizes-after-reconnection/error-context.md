# Test info

- Name: POS route smoke coverage >> offline cash order queues in IndexedDB and synchronizes after reconnection
- Location: C:\Users\DELL\Herd\tidypos\tests\e2e\pos-smoke.spec.js:38:5

# Error details

```
Error: page.goto: net::ERR_CONNECTION_REFUSED at http://tidypos.test/
Call log:
  - navigating to "http://tidypos.test/", waiting until "load"

    at login (C:\Users\DELL\Herd\tidypos\tests\e2e\pos-smoke.spec.js:12:16)
    at C:\Users\DELL\Herd\tidypos\tests\e2e\pos-smoke.spec.js:40:15
```

# Test source

```ts
   1 | import { test, expect } from '@playwright/test';
   2 |
   3 |
   4 | const email = process.env.E2E_EMAIL;
   5 | const password = process.env.E2E_PASSWORD;
   6 |
   7 | function requireCredentials() {
   8 |     test.skip(!email || !password, 'Set E2E_EMAIL and E2E_PASSWORD to run authenticated E2E tests.');
   9 | }
   10 |
   11 | async function login(page) {
>  12 |     await page.goto('/');
      |                ^ Error: page.goto: net::ERR_CONNECTION_REFUSED at http://tidypos.test/
   13 |     const signInForm = page.locator('form').filter({ has: page.getByRole('button', { name: /sign in/i }) });
   14 |     await signInForm.getByPlaceholder('Email').fill(email);
   15 |     await signInForm.locator('input[type="password"]').fill(password);
   16 |     await signInForm.getByRole('button', { name: /sign in/i }).click();
   17 |     await expect(page).toHaveURL(/\/admin\/dashboard/);
   18 | }
   19 |
   20 | test.describe('POS route smoke coverage', () => {
   21 |     test('authenticated user can open the online Livewire POS', async ({ page }) => {
   22 |         requireCredentials();
   23 |         await login(page);
   24 |         await page.goto('/admin/online-pos');
   25 |         await expect(page).toHaveURL(/\/admin\/online-pos/);
   26 |         await expect(page.locator('[wire\\:id]').first()).toBeVisible();
   27 |     });
   28 |
   29 |     test('authenticated user can open the offline Vue/PWA POS shell', async ({ page }) => {
   30 |         requireCredentials();
   31 |         await login(page);
   32 |         await page.goto('/admin/pos');
   33 |         await expect(page).toHaveURL(/\/admin\/pos/);
   34 |         await expect(page.locator('#pos-app')).toBeVisible();
   35 |         await expect(page.getByText('Online', { exact: true })).toBeVisible();
   36 |     });
   37 |
   38 |     test('offline cash order queues in IndexedDB and synchronizes after reconnection', async ({ page }) => {
   39 |         requireCredentials();
   40 |         await login(page);
   41 |         await page.goto('/admin/pos');
   42 |         await expect(page.locator('#pos-app')).toBeVisible();
   43 |         await expect(page.getByPlaceholder('Search Here')).toBeVisible();
   44 |
   45 |         const firstService = page.locator('a[data-bs-target="#servicetype"]').first();
   46 |         await expect(firstService).toBeVisible();
   47 |         await firstService.click();
   48 |         await page.locator('#servicetype button[type="submit"]').click();
   49 |         await expect(page.locator('#cartTable, table').first()).toBeVisible();
   50 |
   51 |         await page.context().setOffline(true);
   52 |         await expect(page.getByText('Offline Mode')).toBeVisible();
   53 |         await page.getByRole('button', { name: 'Cash' }).click();
   54 |
   55 |         const queuedUuid = await page.waitForFunction(async () => {
   56 |             const request = indexedDB.open('TidyPOSDatabase');
   57 |             return await new Promise((resolve, reject) => {
   58 |                 request.onerror = () => reject(request.error);
   59 |                 request.onsuccess = () => {
   60 |                     const database = request.result;
   61 |                     const transaction = database.transaction('syncQueue', 'readonly');
   62 |                     const store = transaction.objectStore('syncQueue');
   63 |                     const cursorRequest = store.openCursor();
   64 |                     cursorRequest.onsuccess = () => {
   65 |                         const cursor = cursorRequest.result;
   66 |                         if (!cursor) return resolve(null);
   67 |                         const value = cursor.value;
   68 |                         resolve(value.type === 'order' && value.status === 'pending' ? value.data.uuid : null);
   69 |                     };
   70 |                 };
   71 |             });
   72 |         }, null, { timeout: 10_000 });
   73 |
   74 |         expect(await queuedUuid.jsonValue()).toMatch(/^[0-9a-f-]{36}$/i);
   75 |         const uuid = await queuedUuid.jsonValue();
   76 |         const queuedPayload = await page.evaluate(async (orderUuid) => {
   77 |             const request = indexedDB.open('TidyPOSDatabase');
   78 |             return await new Promise((resolve, reject) => {
   79 |                 request.onerror = () => reject(request.error);
   80 |                 request.onsuccess = () => {
   81 |                     const database = request.result;
   82 |                     const transaction = database.transaction('syncQueue', 'readonly');
   83 |                     const store = transaction.objectStore('syncQueue');
   84 |                     const cursorRequest = store.openCursor();
   85 |                     cursorRequest.onsuccess = () => {
   86 |                         const cursor = cursorRequest.result;
   87 |                         if (!cursor) return resolve(null);
   88 |                         const value = cursor.value;
   89 |                         resolve(value.type === 'order' && value.data.uuid === orderUuid ? value.data : null);
   90 |                     };
   91 |                 };
   92 |             });
   93 |         }, uuid);
   94 |
   95 |         const syncResponse = page.waitForResponse(response =>
   96 |             response.url().includes('/api/pos/sync-orders') && response.request().method() === 'POST'
   97 |         );
   98 |
   99 |         await page.context().setOffline(false);
  100 |         const response = await syncResponse;
  101 |         expect(response.ok()).toBeTruthy();
  102 |         const body = await response.json();
  103 |         expect(body.synced_orders).toHaveProperty(uuid);
  104 |         const firstServerId = body.synced_orders[uuid];
  105 |
  106 |         const replay = await page.evaluate(async (order) => {
  107 |             const response = await fetch('/api/pos/sync-orders', {
  108 |                 method: 'POST',
  109 |                 headers: {
  110 |                     'Content-Type': 'application/json',
  111 |                     'Accept': 'application/json',
  112 |                     'Authorization': `Bearer ${window.PosConfig.apiToken}`,
```