import { expect, test } from 'playwright/test';
import {
    assertBrowserRuntime,
    guardBrowserRuntime,
    publicLogin,
    staffLogin,
} from './support/browser.mjs';

test.beforeEach(async ({ page }) => guardBrowserRuntime(page));
test.afterEach(async ({ page }) => assertBrowserRuntime(page));

test('candidate and employer accounts cannot enter Filament', async ({ page }) => {
    for (const email of ['candidate@smv-acceptance.test', 'employer@smv-acceptance.test']) {
        await publicLogin(page, email);
        const response = await page.goto('/dashboard');
        expect(response?.status()).toBe(403);
        const report = await page.goto('/dashboard/rapportage/outbound-apply-clicks');
        expect(report?.status()).toBe(403);
        await page.context().clearCookies();
    }
});

test('editor access remains editorial and excludes protected administration', async ({ page }) => {
    await staffLogin(page, 'editor@smv-acceptance.test');
    await expect(page.getByText('Dashboard').first()).toBeVisible();

    for (const path of ['/dashboard/users', '/dashboard/rapportage/outbound-apply-clicks']) {
        const response = await page.goto(path);
        expect(response?.status()).toBe(403);
    }

    await page.goto('/dashboard/vacancies');
    await expect(page.getByText('Vacancies').first()).toBeVisible();
});

test('staff User form exposes controlled roles and read-only legacy classification', async ({ page }) => {
    await staffLogin(page, 'super_admin@smv-acceptance.test');
    await page.goto('/dashboard/users/create');
    await expect(page.getByRole('button', { name: 'Selecteer een optie' })).toBeVisible();
    await expect(page.getByText('Legacy registratieclassificatie')).toHaveCount(0);

    await page.goto('/dashboard/users');
    const row = page.getByRole('row').filter({
        has: page.getByText('admin@smv-acceptance.test', { exact: true }),
    });
    await expect(row).toBeVisible();
    await row.getByRole('button').last().click();
    await page.getByRole('link', { name: 'Bewerken' }).click();

    await expect(page.getByRole('button', { name: 'Beheerder Remove Beheerder' })).toBeVisible();
    await expect(page.getByText('Legacy registratieclassificatie')).toBeVisible();
    await expect(page.getByText('Alleen voor achterwaartse compatibiliteit')).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Legacy registratieclassificatie' })).toHaveCount(0);
});

test('ordinary admin cannot select a protected super-admin role', async ({ page }) => {
    await staffLogin(page, 'admin@smv-acceptance.test');
    await page.goto('/dashboard/users/create');

    const roles = page.getByRole('button', { name: 'Selecteer een optie' });
    await roles.click();
    await expect(page.getByRole('option', { name: 'Beheerder', exact: true })).toBeVisible();
    await expect(page.getByRole('option', { name: 'Redacteur', exact: true })).toBeVisible();
    await expect(page.getByRole('option', { name: 'Superbeheerder', exact: true })).toHaveCount(0);
});

test('admin can inspect approval, ranking, package, Order, credit and import operations', async ({ page }) => {
    await staffLogin(page, 'admin@smv-acceptance.test');

    const routes = [
        ['/dashboard/vacancies', 'Imported vacature wacht op review'],
        ['/dashboard/companies', 'Noorderlicht Sales Studio'],
        ['/dashboard/commercieel/vacatureranking', 'Vacatureranking'],
        ['/dashboard/packages', 'Standaard'],
        ['/dashboard/orders', 'SMV-ACCEPTANCE-CANCELLABLE'],
        ['/dashboard/placement-credits', 'SMV-ACCEPTANCE-CREDIT-SUPERIOR'],
        ['/dashboard/import-sources', 'Acceptance — trusted fixture feed'],
    ];

    for (const [path, fixture] of routes) {
        await page.goto(path);
        await expect(page.getByText(fixture, { exact: false }).first()).toBeVisible();
    }

    await page.goto('/dashboard/vacancies');
    await page.getByRole('searchbox', { name: 'Zoeken', exact: true }).fill('Betaalde plaatsing wacht op beoordeling');
    await expect(page.getByRole('row').filter({
        hasText: 'Betaalde plaatsing wacht op beoordeling',
    })).toContainText('In afwachting');

    await page.goto('/dashboard/commercieel/vacatureranking');
    await expect(page.locator('input[type="number"]').first()).toBeVisible();
    const rankingCheckbox = page.getByRole('checkbox', { name: /bulkacties/ }).first();
    await rankingCheckbox.check();
    await expect(page.getByRole('button', { name: 'Acties openen' })).toBeVisible();

    await page.goto('/dashboard/orders');
    const cancellable = page.getByRole('row').filter({ hasText: 'SMV-ACCEPTANCE-CANCELLABLE' });
    await cancellable.getByRole('link', { name: 'Bekijken / acties' }).click();
    await expect(page.getByRole('button', { name: 'Onbetaalde bestelling annuleren' })).toBeVisible();

    await page.goto('/dashboard/orders');
    const paid = page.getByRole('row').filter({ hasText: 'SMV-ACCEPTANCE-PAID' });
    await paid.getByRole('link', { name: 'Bekijken / acties' }).click();
    await expect(page.getByRole('button', { name: 'Volledig terugbetalen' })).toBeVisible();
});

test('staff reporting shows raw analytics and offers an authorized CSV', async ({ page }) => {
    await staffLogin(page, 'admin@smv-acceptance.test');
    await page.goto('/dashboard/rapportage/outbound-apply-clicks');

    await expect(page.getByText('Detailweergaven (ruw)').first()).toBeVisible();
    await expect(page.getByText('Externe clicks (ruw)').first()).toBeVisible();
    await expect(page.getByRole('row').filter({
        hasText: 'Business developer externe sollicitatielink',
    })).toBeVisible();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Gefilterde CSV downloaden' }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/^outbound-apply-clicks-.*\.csv$/);
});
