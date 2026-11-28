import { expect, test } from 'playwright/test';
import {
    assertBrowserRuntime,
    expectNoHorizontalOverflow,
    guardBrowserRuntime,
    publicLogin,
} from './support/browser.mjs';

test.beforeEach(async ({ page }) => guardBrowserRuntime(page));
test.afterEach(async ({ page }) => assertBrowserRuntime(page));

test('unauthenticated account and save actions return to public login', async ({ page }) => {
    await page.goto('/account');
    await expect(page).toHaveURL(/\/inloggen/);

    await page.goto('/vacatures/acceptance-vacancy-placement-superior-active');
    await page.getByRole('button', { name: 'Bewaar vacature' }).first().click();
    await expect(page).toHaveURL(/\/inloggen/);
    await expect(page.getByRole('heading', { name: 'Inloggen' })).toBeVisible();
});

test('candidate registration creates a non-staff account without a verification detour', async ({ page }) => {
    await page.goto('/registreren/werkzoekende');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Account aanmaken als werkzoekende');
    await page.getByLabel('Naam').fill('Browser Kandidaat');
    await page.getByLabel('E-mailadres').fill('browser-candidate@smv-acceptance.test');
    await page.locator('input[name="password"]').fill('browser1234');
    await page.locator('input[name="password_confirmation"]').fill('browser1234');
    await page.getByRole('button', { name: 'Account aanmaken' }).click();

    await expect(page).toHaveURL(/\/account$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Browser Kandidaat');
    await expect(page.getByRole('heading', { name: 'Mijn overzicht' })).toBeVisible();

    const response = await page.goto('/dashboard');
    expect(response?.status()).toBe(403);
});

test('candidate can save fixtures and inspect the internal application journey', async ({ page }) => {
    await publicLogin(page, 'candidate@smv-acceptance.test');
    await expect(page.getByRole('heading', { name: 'Mijn overzicht' })).toBeVisible();

    await page.goto('/vacatures/acceptance-vacancy-placement-superior-active');
    const saveVacancy = page
        .locator('form[action*="/vacatures/acceptance-vacancy-placement-superior-active/bewaren"]')
        .getByRole('button');
    await saveVacancy.click();
    await expect(saveVacancy).toHaveAttribute('aria-pressed', 'true');
    await page.goto('/account/bewaarde-vacatures');
    await expect(page.getByText('Actieve betaalde Superior-plaatsing').first()).toBeVisible();

    await page.goto('/bedrijven');
    const companyCard = page.getByRole('article').filter({ hasText: 'Kompas Growth Lab' });
    const saveCompany = companyCard.locator('button[data-saved]');
    await expect(saveCompany).toHaveAccessibleName('Bewaar bedrijf');
    await saveCompany.click();
    await expect(saveCompany).toHaveAttribute('aria-pressed', 'true');
    await page.goto('/account/bewaarde-bedrijven');
    await expect(page.getByText('Kompas Growth Lab').first()).toBeVisible();

    await page.goto('/vacatures/acceptance-vacancy-manual-internal/solliciteren');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Accountmanager interne sollicitaties');
    await expect(page.getByLabel('Naam')).toBeVisible();
    await expect(page.getByLabel('Motivatie')).toBeVisible();

    await page.goto('/account/sollicitaties');
    await expect(page.getByText('Accountmanager interne sollicitaties').first()).toBeVisible();
    await expect(page.getByText('In behandeling').first()).toBeVisible();
});

test('dual-context user keeps candidate and employer functions without staff access', async ({ page }) => {
    await publicLogin(page, 'dual@smv-acceptance.test');
    await expect(page.getByRole('heading', { name: 'Werkgeversoverzicht' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ook in dit account' })).toBeVisible();
    await expect(page.getByText('Dubbelrol Growth Partners').first()).toBeVisible();

    await page.goto('/account/sollicitaties');
    await expect(page.getByText('Accountmanager interne sollicitaties').first()).toBeVisible();

    await page.goto('/account/bestellingen');
    await expect(page.getByText('SMV-ACCEPTANCE-PAID')).toHaveCount(0);
    await page.goto('/account/plaatsingsrechten');
    await expect(page.getByText('SMV-ACCEPTANCE-CREDIT-SUPERIOR')).toHaveCount(0);

    const response = await page.goto('/dashboard');
    expect(response?.status()).toBe(403);
});

test('employer can inspect commerce states and create a safe local draft', async ({ page }) => {
    await publicLogin(page, 'employer@smv-acceptance.test');
    await expect(page.getByRole('heading', { name: 'Werkgeversoverzicht' })).toBeVisible();
    await expect(page.getByText('Beschikbare rechten').first()).toBeVisible();

    await page.goto('/account/bestellingen');
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/bestellingen/i);
    await expect(page.getByText('SMV-ACCEPTANCE-PAID').first()).toBeVisible();
    await expect(page.getByText(/Betaald/).first()).toBeVisible();

    await page.goto('/vacature-plaatsen');
    await page.getByRole('article').filter({ hasText: 'Standaard' }).getByRole('button').click();
    await expect(page).toHaveURL(/\/vacature-plaatsen\/vacature$/);

    const company = page.getByRole('button', { name: /Bedrijf/ });
    await company.focus();
    await page.keyboard.press('Enter');
    await expect(company).toHaveAttribute('aria-expanded', 'true');
    const companyOption = page.getByRole('option', { name: 'Noorderlicht Sales Studio' });
    await companyOption.focus();
    await page.keyboard.press('Enter');
    await expect(company).toContainText('Noorderlicht Sales Studio');
    await page.getByLabel('Functietitel').fill('Browser smoke conceptvacature');
    await page.getByRole('textbox', { name: 'Vacaturebeschrijving' }).first().fill('Veilige fictieve browseromschrijving voor de lokale acceptatiesuite.');
    await page.locator('input#location').fill('Utrecht');

    const mode = page.getByLabel('Sollicitatiemethode');
    await mode.selectOption('email');
    await expect(page.getByLabel('Sollicitatie-e-mailadres')).toBeVisible();
    await mode.selectOption('external');
    await expect(page.getByLabel('Externe sollicitatielink')).toBeVisible();
    await mode.selectOption('internal');
    await expect(page.getByText('bestaande beveiligde sollicitatieformulier')).toBeVisible();

    await page.getByRole('button', { name: /Opslaan en voorbeeld/ }).click();
    await expect(page).toHaveURL(/\/vacature-plaatsen\/vacature\/browser-smoke-conceptvacature\/voorbeeld$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Browser smoke conceptvacature');

    await page.goto('/account/plaatsingsrechten');
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/plaatsingsrechten/i);
    await expect(page.getByText(/beschikbaar/i).first()).toBeVisible();
    await expect(page.getByText(/toegewezen/i).first()).toBeVisible();
    await expect(page.getByText(/actief/i).first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'Recht gebruiken' }).first()).toBeVisible();

    await page.goto('/account/vacatures');
    await expect(page.getByText('Browser smoke conceptvacature').first()).toBeVisible();
    await expect(page.getByText(/Concept/).first()).toBeVisible();
});

test('employer dashboard remains usable at representative account widths', async ({ page }) => {
    await publicLogin(page, 'employer@smv-acceptance.test');

    for (const width of [320, 375, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto('/account');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByRole('heading', { name: 'Werkgeversoverzicht' })).toBeVisible();

        await page.goto('/account/plaatsingsrechten');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByRole('heading', { level: 1 })).toContainText(/plaatsingsrechten/i);

        await page.goto('/account/bestellingen');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByRole('heading', { level: 1 })).toContainText(/bestellingen/i);
    }
});
