import { expect } from 'playwright/test';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { applicationEnvironment, phpBinary, repositoryRoot } from './environment.mjs';

const runtimeState = new WeakMap();

export async function guardBrowserRuntime(page) {
    const state = { errors: [], failedRequests: [], serverErrors: [], missingAssets: [], viteAssets: new Set() };
    const baseOrigin = new URL(
        process.env.SMV_BROWSER_MODE === 'staging'
            ? process.env.SMV_STAGING_BASE_URL
            : (process.env.SMV_BROWSER_BASE_URL ?? 'http://127.0.0.1:4173'),
    ).origin;
    runtimeState.set(page, state);

    await page.context().route('**/*', async (route) => {
        const url = new URL(route.request().url());

        if (url.origin !== baseOrigin) {
            await route.fulfill({ status: 204, body: '' });
            return;
        }

        await route.continue();
    });

    page.on('pageerror', (error) => state.errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error' && ! message.text().startsWith('Failed to load resource:')) {
            state.errors.push(message.text());
        }
    });
    page.on('requestfailed', (request) => {
        if (request.url().startsWith(baseOrigin)) {
            state.failedRequests.push(`${request.method()} ${request.url()}: ${request.failure()?.errorText}`);
        }
    });
    page.on('response', (response) => {
        const url = response.url();

        if (url.startsWith(baseOrigin) && url.includes('/build/')) {
            state.viteAssets.add(url);

            if (response.status() >= 400) {
                state.missingAssets.push(`${response.status()} ${url}`);
            }
        }

        if (url.startsWith(baseOrigin) && response.status() >= 500) {
            state.serverErrors.push(`${response.status()} ${response.request().method()} ${url}`);
        }
    });
}

export function assertBrowserRuntime(page) {
    const state = runtimeState.get(page);

    expect(state?.errors ?? [], 'fatal browser-console errors').toEqual([]);
    expect(state?.failedRequests ?? [], 'failed local browser requests').toEqual([]);
    expect(state?.serverErrors ?? [], 'HTTP 5xx responses').toEqual([]);
    expect(state?.missingAssets ?? [], 'missing critical Vite assets').toEqual([]);
}

export function expectViteAssets(page) {
    expect(runtimeState.get(page)?.viteAssets.size ?? 0, 'at least one Vite asset was loaded').toBeGreaterThan(0);
}

export async function expectNoHorizontalOverflow(page) {
    const dimensions = await page.evaluate(() => ({
        viewport: document.documentElement.clientWidth,
        content: document.documentElement.scrollWidth,
    }));

    expect(dimensions.content, `horizontal overflow: ${JSON.stringify(dimensions)}`)
        .toBeLessThanOrEqual(dimensions.viewport + 1);
}

export function analyticsCounts() {
    const result = spawnSync(phpBinary(), [
        path.join('tests', 'Browser', 'support', 'analytics-state.php'),
    ], {
        cwd: repositoryRoot,
        env: applicationEnvironment(),
        encoding: 'utf8',
        stdio: 'pipe',
        windowsHide: true,
    });

    if (result.status !== 0) {
        throw new Error(`Could not read isolated analytics state.\n${result.error ?? ''}\n${result.stdout}\n${result.stderr}`);
    }

    return JSON.parse(result.stdout);
}

export async function publicLogin(page, email) {
    await page.goto('/inloggen');
    await page.getByLabel('E-mailadres').fill(email);
    await page.getByLabel('Wachtwoord').fill('acceptance-demo-password');
    await page.getByRole('button', { name: 'Inloggen' }).click();
    await expect(page).toHaveURL(/\/account(?:$|\?)/);
}

export async function staffLogin(page, email) {
    await page.goto('/dashboard/login');
    await page.locator('input[type="email"]').fill(email);
    await page.locator('input[type="password"]').fill('acceptance-demo-password');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:$|\/)/);
    await expect(page.getByRole('heading', { level: 1, name: 'Dashboard' })).toBeVisible();
}
