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
