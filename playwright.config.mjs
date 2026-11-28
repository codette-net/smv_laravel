import { defineConfig, devices } from 'playwright/test';
import path from 'node:path';
import process from 'node:process';

const staging = process.env.SMV_BROWSER_MODE === 'staging';
const baseURL = staging
    ? process.env.SMV_STAGING_BASE_URL
    : (process.env.SMV_BROWSER_BASE_URL ?? 'http://127.0.0.1:4173');

if (staging && ! baseURL) {
    throw new Error('SMV_STAGING_BASE_URL is required for read-only staging smoke.');
}

const node = JSON.stringify(process.execPath);
const server = JSON.stringify(path.resolve('tests/Browser/serve.mjs'));

export default defineConfig({
    testDir: './tests/Browser',
    testIgnore: staging ? ['**/public.spec.mjs', '**/accounts.spec.mjs', '**/staff.spec.mjs'] : ['**/staging-readonly.spec.mjs'],
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    forbidOnly: Boolean(process.env.CI),
    timeout: 45_000,
    expect: { timeout: 8_000 },
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    outputDir: 'test-results',
    webServer: staging ? undefined : {
        command: `${node} ${server}`,
        url: baseURL,
        reuseExistingServer: false,
        timeout: 120_000,
    },
    use: {
        ...devices['Desktop Chrome'],
        baseURL,
        locale: 'nl-NL',
        timezoneId: 'Europe/Amsterdam',
        colorScheme: 'light',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        { name: staging ? 'chromium-read-only' : 'chromium', use: { browserName: 'chromium' } },
    ],
});
