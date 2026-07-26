import { test, expect } from '@playwright/test';


const email = process.env.E2E_EMAIL;
const password = process.env.E2E_PASSWORD;

function requireCredentials() {
    test.skip(!email || !password, 'Set E2E_EMAIL and E2E_PASSWORD to run authenticated E2E tests.');
}

async function login(page) {
    await page.goto('/');
    const signInForm = page.locator('form').filter({ has: page.getByRole('button', { name: /sign in/i }) });
    await signInForm.getByPlaceholder('Email').fill(email);
    await signInForm.locator('input[type="password"]').fill(password);
    await signInForm.getByRole('button', { name: /sign in/i }).click();
    await expect(page).toHaveURL(/\/admin\/dashboard/);
}

test.describe('POS route smoke coverage', () => {
    test('authenticated user can open the online Livewire POS', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/online-pos');
        await expect(page).toHaveURL(/\/admin\/online-pos/);
        await expect(page.locator('[wire\\:id]').first()).toBeVisible();
    });

    test('authenticated user can open the offline Vue/PWA POS shell', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page).toHaveURL(/\/admin\/pos/);
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect(page.getByText('Online', { exact: true })).toBeVisible();
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
                            ? { status: value.status, retryCount: value.retry_count, error: value.error_message, nextRetryAt: value.next_retry_at }
                            : null);
                    };
                };
            });
        }, uuid)).toEqual({
            status: 'retryable_failure',
            retryCount: 1,
            error: 'E2E forced temporary failure',
        });

        const retryMetadata = await page.evaluate(async orderUuid => {
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
                        resolve(cursor.value.data?.uuid === orderUuid ? cursor.value.next_retry_at : null);
                    };
                };
            });
        }, uuid);
        expect(retryMetadata).toBeGreaterThan(Date.now());

        // Release the deliberate backoff so this test can verify recovery without waiting 30 seconds.
        await page.evaluate(async orderUuid => {
            const request = indexedDB.open('TidyPOSDatabase');
            await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readwrite');
                    const store = transaction.objectStore('syncQueue');
                    const cursorRequest = store.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve();
                        if (cursor.value.data?.uuid === orderUuid) {
                            cursor.value.next_retry_at = 0;
                            store.put(cursor.value);
                        }
                        resolve();
                    };
                };
            });
        }, uuid);

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

    test('synchronizes six queued orders in batches of five and isolates item failures', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page.locator('#pos-app')).toBeVisible();

        const queuedUuids = await page.evaluate(async () => {
            const userId = String(window.PosConfig.user.id);
            const uuids = Array.from({ length: 6 }, (_, index) => `e2e-batch-${Date.now()}-${index}`);
            const request = indexedDB.open('TidyPOSDatabase');
            await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readwrite');
                    const store = transaction.objectStore('syncQueue');
                    for (const uuid of uuids) {
                        store.add({
                            user_id: userId,
                            uuid,
                            type: 'order',
                            data: { uuid, customer_name: 'E2E Batch Customer', total: 10 },
                            timestamp: Date.now(),
                            status: 'pending',
                            retry_count: 0
                        });
                    }
                    transaction.oncomplete = resolve;
                    transaction.onerror = () => reject(transaction.error);
                };
            });
            return uuids;
        });

        const requests = [];
        await page.route('**/api/pos/sync-orders', async route => {
            const payload = route.request().postDataJSON();
            requests.push(payload.orders);
            const failedUuid = queuedUuids[1];
            const syncedOrders = {};
            const failed = {};
            for (const order of payload.orders) {
                if (order.uuid === failedUuid) {
                    failed[order.uuid] = 'E2E validation failure';
                } else {
                    syncedOrders[order.uuid] = `server-${order.uuid}`;
                }
            }
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ synced_orders: syncedOrders, requires_approval: {}, failed })
            });
        });

        await page.reload();
        await expect.poll(() => requests.length).toBe(2);

        expect(requests.map(batch => batch.length)).toEqual([5, 1]);
        expect(requests.flat().map(order => order.uuid)).toEqual(queuedUuids);

        const queueState = await page.evaluate(async uuids => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const database = request.result;
                    const transaction = database.transaction('syncQueue', 'readonly');
                    const store = transaction.objectStore('syncQueue');
                    const result = [];
                    const cursorRequest = store.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return resolve(result);
                        if (uuids.includes(cursor.value.data?.uuid)) {
                            result.push({ uuid: cursor.value.data.uuid, status: cursor.value.status, error: cursor.value.error_message });
                        }
                        cursor.continue();
                    };
                };
            });
        }, queuedUuids);

        expect(queueState).toEqual([{
            uuid: queuedUuids[1],
            status: 'permanent_failure',
            error: 'E2E validation failure'
        }]);
    });

    test('expired POS token preserves queue and resumes after re-authentication', async ({ page }) => {
        requireCredentials();
        await login(page);
        await page.goto('/admin/pos');
        await expect(page.locator('#pos-app')).toBeVisible();
        await expect(page.getByPlaceholder('Search Here')).toBeVisible();

        const firstService = page.locator('a[data-bs-target="#servicetype"]').first();
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
                    const transaction = request.result.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        resolve(cursor && cursor.value.type === 'order' ? cursor.value.data.uuid : null);
                    };
                };
            });
        }, null, { timeout: 10_000 });
        const uuid = await queuedUuid.jsonValue();
        expect(uuid).toMatch(/^[0-9a-f-]{36}$/i);

        let rejectedOnce = false;
        await page.route('**/api/pos/sync-orders', async route => {
            if (!rejectedOnce) {
                rejectedOnce = true;
                await route.fulfill({
                    status: 401,
                    contentType: 'application/json',
                    body: JSON.stringify({ message: 'Unauthenticated' }),
                });
                return;
            }
            await route.continue();
        });

        const rejectedResponse = page.waitForResponse(response =>
            response.url().includes('/api/pos/sync-orders') && response.status() === 401
        );
        await page.context().setOffline(false);
        await rejectedResponse;
        await expect(page.getByText('Session Expired')).toBeVisible();
        await expect(page.getByText('Please verify your identity to securely resume synchronization.')).toBeVisible();

        const retained = await page.evaluate(async orderUuid => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const transaction = request.result.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        resolve(Boolean(cursor && cursor.value.data?.uuid === orderUuid));
                    };
                };
            });
        }, uuid);
        expect(retained).toBe(true);

        const resumedResponse = page.waitForResponse(response =>
            response.url().includes('/api/pos/sync-orders') && response.ok()
        );
        await page.locator('.reauth-modal input[type="password"]').fill(password);
        await page.getByRole('button', { name: 'Unlock & Resume' }).click();
        await expect(page.getByText('Session Expired')).toBeHidden();
        await resumedResponse;

        await expect.poll(async () => page.evaluate(async orderUuid => {
            const request = indexedDB.open('TidyPOSDatabase');
            return await new Promise((resolve, reject) => {
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const transaction = request.result.transaction('syncQueue', 'readonly');
                    const cursorRequest = transaction.objectStore('syncQueue').openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        resolve(Boolean(cursor && cursor.value.data?.uuid === orderUuid));
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
