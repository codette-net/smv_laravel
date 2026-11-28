import { expect, test } from 'playwright/test';
import { assertBrowserRuntime, guardBrowserRuntime } from './support/browser.mjs';

test.beforeEach(async ({ page }) => guardBrowserRuntime(page));
test.afterEach(async ({ page }) => assertBrowserRuntime(page));

test('shared staging exposes only the read-only public critical path', async ({ page }) => {
    for (const [path, heading] of [
        ['/', /Sales- en marketingvacatures/],
        ['/vacatures', /Vind jouw volgende vacature/],
        ['/bedrijven', /Ontdek bedrijven/],
        ['/blog', /Blog|Inzichten|Artikelen/],
        ['/contact', /Bespreek uw vacature of samenwerking/],
        ['/tarieven', /Kies de zichtbaarheid/],
        ['/adverteren', /Uw vacature onder de aandacht/],
    ]) {
        const response = await page.goto(path);
        expect(response?.status()).toBeLessThan(400);
        await expect(page.getByRole('heading', { level: 1 })).toContainText(heading);
    }
});
