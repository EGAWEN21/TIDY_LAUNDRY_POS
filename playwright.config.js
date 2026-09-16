import { defineConfig, devices } from '@playwright/test';

const externalBaseUrl = process.env.E2E_BASE_URL;
const baseURL = externalBaseUrl || 'http://127.0.0.1:43127';
const phpBinary = process.env.E2E_PHP_BINARY || 'php';
const quotedPhpBinary = `"${phpBinary.replaceAll('"', '\\"')}"`;

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 10_000,
    },
    fullyParallel: false,
    reporter: process.env.CI ? 'github' : 'list',
    webServer: externalBaseUrl ? undefined : {
        command: `${quotedPhpBinary} tests/e2e/server.php`,
        url: baseURL,
        reuseExistingServer: false,
        timeout: 120_000,
    },
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        ...devices['Desktop Chrome'],
        launchOptions: {
            executablePath: process.env.PLAYWRIGHT_EXECUTABLE_PATH,
        },
    },
});
