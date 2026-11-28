import { expect, test } from 'playwright/test';
import {
    analyticsCounts,
    assertBrowserRuntime,
    expectNoHorizontalOverflow,
    expectViteAssets,
    guardBrowserRuntime,
} from './support/browser.mjs';

test.beforeEach(async ({ page }) => guardBrowserRuntime(page));
test.afterEach(async ({ page }) => assertBrowserRuntime(page));

test('public visitor can navigate the critical content routes', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Sales- en marketingvacatures');
    await expect(page.getByRole('navigation', { name: 'Hoofdnavigatie' })).toBeVisible();
    expectViteAssets(page);

    for (const [path, heading] of [
        ['/vacatures', /Vind jouw volgende vacature/],
        ['/bedrijven', /Ontdek bedrijven/],
        ['/blog', /Blog|Inzichten|Artikelen/],
        ['/contact', /Bespreek uw vacature of samenwerking/],
        ['/tarieven', /Kies de zichtbaarheid/],
        ['/adverteren', /Uw vacature onder de aandacht/],
    ]) {
        await page.goto(path);
        await expect(page.getByRole('heading', { level: 1 })).toContainText(heading);
    }

    await page.goto('/bedrijven/acceptance-company-complete');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Noorderlicht Sales Studio');

    await page.goto('/blog/zo-bouw-je-een-moderne-contentstrategie');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('moderne contentstrategie');
});

test('vacancy search, disclosure, clear action and pagination work', async ({ page }) => {
    await page.goto('/vacatures');
    const search = page.getByRole('searchbox', { name: 'Zoeken' });
    await search.fill('Business developer');
    await expect(page).toHaveURL(/zoek=Business(?:\+|%20)developer/);
    await expect(page.getByText('Business developer externe sollicitatielink').first()).toBeVisible();

    const disclosure = page.getByRole('button', { name: /Meer filters/ });
    await disclosure.click();
    await expect(disclosure).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByLabel('Sector')).toBeVisible();

    await page.getByRole('link', { name: 'Wis filters' }).first().click();
    await expect(page).toHaveURL(/\/vacatures$/);
    await expect(search).toHaveValue('');

    await page.goto('/');
    const pageTwo = page.getByRole('link', { name: 'Go to page 2' });
    await expect(pageTwo).toBeVisible();
    await pageTwo.click();
    await expect(page).toHaveURL(/page=2/);
    await expect(page.getByRole('heading', { name: /vacatures/i }).first()).toBeVisible();
});

test('vacancy and company detail routes expose their intended public content', async ({ page }) => {
    await page.goto('/vacatures/acceptance-vacancy-manual-internal');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Accountmanager interne sollicitaties');
    await expect(page.getByRole('link', { name: /Solliciteer nu/ }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Bewaar vacature/ }).first()).toBeVisible();

    await page.goto('/bedrijven/acceptance-company-minimal');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Kompas Growth Lab');
    await expect(page.getByRole('heading', { name: 'Vacatures bij Kompas Growth Lab' })).toBeVisible();
});

test('eligible detail GET and external apply record analytics while HEAD does not', async ({ page }) => {
    const baseline = analyticsCounts();
    const head = await page.request.head('/vacatures/acceptance-vacancy-manual-external');
    expect(head.ok()).toBe(true);
    expect(analyticsCounts()).toEqual(baseline);

    await page.goto('/vacatures/acceptance-vacancy-manual-external');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Business developer externe sollicitatielink');
    expect(analyticsCounts()).toEqual({ ...baseline, views: baseline.views + 1 });

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('link', { name: /Solliciteer nu/ }).first().click();
    const popup = await popupPromise;
    await expect.poll(() => analyticsCounts()).toEqual({
        views: baseline.views + 1,
        clicks: baseline.clicks + 1,
    });
    await popup.close();
});

test('representative responsive viewports have reachable navigation and no horizontal overflow', async ({ page }) => {
    for (const width of [320, 375, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: width < 768 ? 760 : 900 });
        await page.goto('/');
        await expectNoHorizontalOverflow(page);

        if (width < 1024) {
            const menu = page.getByRole('button', { name: 'Menu' });
            await menu.click();
            await expect(page.getByRole('navigation', { name: 'Mobiele hoofdnavigatie' })).toBeVisible();
            await page.keyboard.press('Escape');
            await expect(menu).toBeFocused();
        }

        await page.goto('/vacatures/acceptance-vacancy-manual-internal');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByRole('link', { name: /Solliciteer nu/ }).first()).toBeVisible();

        await page.goto('/vacatures');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByRole('searchbox', { name: 'Zoeken' })).toBeVisible();

        await page.goto('/vacatures/acceptance-vacancy-manual-internal/solliciteren');
        await expectNoHorizontalOverflow(page);
        await expect(page.getByLabel('Motivatie')).toBeVisible();
    }
});

test('keyboard focus, custom listbox and reduced motion remain operable', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.setViewportSize({ width: 375, height: 800 });
    await page.goto('/');

    const menu = page.getByRole('button', { name: 'Menu' });
    await menu.focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('navigation', { name: 'Mobiele hoofdnavigatie' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(menu).toBeFocused();

    await page.goto('/vacature-plaatsen');
    await page.getByRole('article').filter({ hasText: 'Standaard' }).getByRole('button').click();
    await expect(page).toHaveURL(/\/inloggen|\/vacature-plaatsen\/account/);
});
