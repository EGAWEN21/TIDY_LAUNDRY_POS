import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 10_000,
    },
    fullyParallel: false,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://localhost',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        ...devices['Desktop Chrome'],
    },
});
