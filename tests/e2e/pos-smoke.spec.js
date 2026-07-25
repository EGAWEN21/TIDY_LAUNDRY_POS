import { test, expect } from '@playwright/test';

const email = process.env.E2E_EMAIL;
const password = process.env.E2E_PASSWORD;

function requireCredentials() {
    test.skip(!email || !password, 'Set E2E_EMAIL and E2E_PASSWORD to run authenticated E2E tests.');
}

async function login(page) {
    await page.goto('/');
    await page.getByPlaceholder('Email').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page).toHaveURL(/\/admin\/dashboard/);
}

test.describe('POS route smoke coverage', () => {
    test('authenticated user can open the online Livewire POS', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/online-pos');
        await expect(page).toHaveURL(/\/admin\/online-pos/);
        await expect(page.locator('[wire\\:id]')).toBeVisible();
    });

    test('authenticated user can open the offline Vue/PWA POS shell', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page).toHaveURL(/\/admin\/pos/);
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect(page.locator('text=Offline POS')).toBeVisible();
    });

    test('offline cash order queues in IndexedDB and synchronizes after reconnection', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect(page.getByPlaceholder('Search Here')).toBeVisible();

        const firstService = page.locator('a[data-bs-target="#servicetype"]').first();
        await expect(firstService).toBeVisible();
        await firstService.click();
        await page.locator('#servicetype button[type="submit"]').click();
        await expect(page.locator('#cartTable, table').first()).toBeVisible();

        await page.context().setOffline(true);
        await expect(page.getByText('Offline Mode')).toBeVisible();
        await page.getByRole('button', { name: 'Cash' }).click();

        const queuedUuid = await page.waitForFunction(async () => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const store = transaction.objectStore('syncQueue');
                    const cursorRequest = store.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(null);
                        const value = cursor.value;
                        resolve(value.type === 'order' && value.status === 'pending' ? value.data.uuid : null);
                    };
                };
            });
        }, null, { timeout: 10_000 });

        expect(await queuedUuid.jsonValue()).toMatch(/^[0-9a-f-]{36}$/i);
        const uuid = await queuedUuid.jsonValue();
        const queuedPayload = await page.evaluate(async (orderUuid) => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const store = transaction.objectStore('syncQueue');
                    const cursorRequest = store.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(null);
                        const value = cursor.value;
                        resolve(value.type === 'order' && value.data.uuid === orderUuid ? value.data : null);
                    };
                };
            });
        }, uuid);

        const syncResponse = page.waitForResponse(response =>
            response.url().includes('/api/pos/sync-orders') && response.request().method() === 'POST'
        );

        await page.context().setOffline(false);
        const response = await syncResponse;
        expect(response.ok()).toBeTruthy();
        const body = await response.json();
        expect(body.synced_orders).toHaveProperty(uuid);
        const firstServerId = body.synced_orders[uuid];

        const replay = await page.evaluate(async (order) => {
            const response = await fetch('/api/pos/sync-orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${window.PosConfig.apiToken}`,
                },
                body: JSON.stringify({ orders: [order] }),
            });
            return { status: response.status, body: await response.json() };
        }, queuedPayload);

        expect(replay.status).toBe(200);
        expect(replay.body.synced_orders[uuid]).toBe(firstServerId);
        expect(replay.body.failed).not.toHaveProperty(uuid);

        await expect.poll(async () => page.evaluate(async (orderUuid) => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const store = transaction.objectStore('syncQueue');
                    const cursorRequest = store.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(false);
                        const value = cursor.value;
                        resolve(value.type === 'order' && value.data.uuid === orderUuid);
                    };
                };
            });
        }, uuid)).toBe(false);
    });

    test('failed offline sync retains the queue and recovers on retry', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect(page.getByPlaceholder('Search Here')).toBeVisible();

        const firstService = page.locator('a[data-bs-target="#servicetype"]').first();
        await expect(firstService).toBeVisible();
        await firstService.click();
        await page.locator('#servicetype button[type="submit"]').click();
        await page.context().setOffline(true);
        await expect(page.getByText('Offline Mode')).toBeVisible();
        await page.getByRole('button', { name: 'Cash' }).click();

        const queuedUuid = await page.waitForFunction(async () => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(null);
                        const value = cursor.value;
                        resolve(value.type === 'order' && value.status === 'pending' ? value.data.uuid : null);
                    };
                };
            });
        }, null, { timeout: 10_000 });
        const uuid = await queuedUuid.jsonValue();
        expect(uuid).toMatch(/^[0-9a-f-]{36}$/i);

        let failedOnce = false;
        await page.route('**/api/pos/sync-orders', async route => {
            if (!failedOnce) {
                failedOnce = true;
                await route.fulfill({
                    status: 503,
                    contentType: 'application/json',
                    body: JSON.stringify({ message: 'E2E forced temporary failure' }),
                });
                return;
            }
            await route.continue();
        });

        const failedResponse = page.waitForResponse(response =>
            response.url().includes('/api/pos/sync-orders') && response.status() === 503
        );
        await page.context().setOffline(false);
        await failedResponse;

        await expect.poll(async () => page.evaluate(async orderUuid => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(null);
                        const value = cursor.value;
                        resolve(value.data?.uuid === orderUuid
                            ? { status: value.status, retryCount: value.retry_count, error: value.error_message }
                            : null);
                    };
                };
            });
        }, uuid)).toEqual({
            status: 'pending',
            retryCount: 1,
            error: 'Server Error: 503',
        });

        const recoveredResponse = page.waitForResponse(response =>
            response.url().includes('/api/pos/sync-orders') && response.ok()
        );
        await page.reload();
        const response = await recoveredResponse;
        expect(response.ok()).toBeTruthy();

        await expect.poll(async () => page.evaluate(async orderUuid => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(false);
                        resolve(cursor.value.data?.uuid === orderUuid);
                    };
                };
            });
        }, uuid)).toBe(false);
    });

    test('offline POS registers its service worker without changing online POS route', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect.poll(async () => page.evaluate(async () => {
            if (!('serviceWorker' in navigator)) return false;
            const registrations = await navigator.serviceWorker.getRegistrations();
            return registrations.some(registration => registration.scope.includes('/admin/pos'));
        })).toBe(true);
    });
});
