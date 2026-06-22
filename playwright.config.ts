import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for AirToShareA end-to-end flows.
 *
 * Covers (see design.md "Testing Strategy"):
 * - Drag-and-drop upload with progress
 * - Dark-theme contrast (paired with axe-core)
 * - Real-time updates via Reverb
 * - Public gallery and PWA install
 *
 * The base URL defaults to the local dev server. Override via PLAYWRIGHT_BASE_URL
 * (for example, when running against a staging environment in CI).
 */
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
    ],
});
