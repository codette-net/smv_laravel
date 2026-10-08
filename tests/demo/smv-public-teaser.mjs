import { chromium } from 'playwright';
import { registry as playwrightRegistry } from 'playwright-core/lib/coreBundle';
import { spawn } from 'node:child_process';
import { mkdir, readFile, rm } from 'node:fs/promises';
import http from 'node:http';
import https from 'node:https';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const baseUrl = (process.env.SMV_DEMO_BASE_URL ?? 'http://smv_laravel.test').replace(/\/$/, '');
const artifactDirectory = path.join(root, 'storage', 'demo');
const rawVideoDirectory = path.join(artifactDirectory, '.playwright');
const rawVideoPath = path.join(rawVideoDirectory, 'smv-platform-teaser-raw.webm');
const finalVideoPath = path.join(artifactDirectory, 'smv-platform-teaser.webm');
const openingTrimSeconds = 0.8;
const fontStylesheetUrl = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,400&display=fallback';

const viewport = { width: 1440, height: 900 };
const timing = {
    openingBeat: 2000,
    homeScroll: 2600,
    bannerBeat: 1400,
    discoveryBeat: 1600,
    discoveryScroll: 2500,
    cardBeat: 1000,
    detailBeat: 1500,
    detailScroll: 2200,
    detailScrollBeat: 1200,
    companyDiscoveryBeat: 1800,
    companyDiscoveryScroll: 2600,
    companyCardsBeat: 1800,
    categoryBeat: 1200,
    companyDetailBeat: 1800,
    companyDetailScroll: 2600,
    endingBeat: 2500,
};

const pause = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

async function trimOpeningFrames(inputPath, outputPath) {
    const ffmpegPath = playwrightRegistry.registry
        .findExecutable('ffmpeg')
        .executablePathOrDie('javascript');

    await new Promise((resolve, reject) => {
        const ffmpeg = spawn(ffmpegPath, [
            '-hide_banner',
            '-loglevel', 'error',
            '-ss', String(openingTrimSeconds),
            '-i', inputPath,
            '-c:v', 'libvpx',
            '-deadline', 'realtime',
            '-cpu-used', '8',
            '-b:v', '2M',
            '-an',
            '-y', outputPath,
        ], { windowsHide: true });
        let errorOutput = '';

        ffmpeg.stderr.on('data', (chunk) => {
            errorOutput = `${errorOutput}${chunk}`.slice(-8000);
        });
        ffmpeg.once('error', reject);
        ffmpeg.once('close', (code) => {
            if (code === 0) {
                resolve();
                return;
            }

            reject(new Error(`Could not trim the teaser opening (FFmpeg exit ${code}).\n${errorOutput.trim()}`));
        });
    });
}

async function prepareFontCache() {
    const headers = {
        accept: 'text/css,*/*;q=0.1',
        'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153 Safari/537.36',
    };
    const stylesheetResponse = await fetch(fontStylesheetUrl, {
        headers,
        signal: AbortSignal.timeout(30000),
    });

    if (!stylesheetResponse.ok) {
        throw new Error(`Could not prepare demo fonts: Google Fonts returned HTTP ${stylesheetResponse.status}.`);
    }

    const stylesheet = await stylesheetResponse.text();
    const fontUrls = [...new Set(
        [...stylesheet.matchAll(/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/g)]
            .map((match) => match[1].replaceAll('"', '').replaceAll("'", '')),
    )];
    const fonts = new Map(await Promise.all(fontUrls.map(async (url) => {
        const response = await fetch(url, {
            headers: { 'user-agent': headers['user-agent'] },
            signal: AbortSignal.timeout(30000),
        });

        if (!response.ok) {
            throw new Error(`Could not prepare demo font ${url}: HTTP ${response.status}.`);
        }

        return [url, Buffer.from(await response.arrayBuffer())];
    })));

    return { stylesheet, fonts };
}

function requestPublicAsset(url, headers, redirectCount = 0) {
    return new Promise((resolve, reject) => {
        const parsedUrl = new URL(url);
        const transport = parsedUrl.protocol === 'https:' ? https : http;
        const request = transport.request(parsedUrl, {
            agent: false,
            headers: {
                ...headers,
                connection: 'close',
            },
            method: 'GET',
        }, (response) => {
            if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
                response.resume();
                if (redirectCount >= 5) {
                    reject(new Error(`Too many redirects while loading ${url}.`));
                    return;
                }

                resolve(requestPublicAsset(
                    new URL(response.headers.location, parsedUrl).href,
                    headers,
                    redirectCount + 1,
                ));
                return;
            }

            const chunks = [];
            response.on('data', (chunk) => chunks.push(chunk));
            response.on('end', () => resolve({
                body: Buffer.concat(chunks),
                headers: Object.fromEntries(Object.entries(response.headers)
                    .filter(([, value]) => value !== undefined)
                    .map(([name, value]) => [name, Array.isArray(value) ? value.join(', ') : String(value)])),
                status: response.statusCode ?? 500,
            }));
        });

        request.setTimeout(15000, () => request.destroy(new Error(`Timed out loading ${url}.`)));
        request.on('error', reject);
        request.end();
    });
}

function withoutDebugbar(html) {
    return html.replace(
        /<!-- Laravel Debugbar Widget -->[\s\S]*<\/script>\s*<\/body>/,
        '</body>',
    );
}

function decodeHtmlUrl(url) {
    return url.replaceAll('&amp;', '&');
}

function firstMatchingHref(html, expression) {
    const match = html.match(expression);

    return match ? decodeHtmlUrl(match[1]) : null;
}

async function preparePublicJourneyDocuments(origin) {
    const documents = new Map();

    const cacheDocument = async (url) => {
        const absoluteUrl = new URL(url, origin).href;
        if (documents.has(absoluteUrl)) {
            return documents.get(absoluteUrl).html;
        }

        const response = await requestPublicAsset(absoluteUrl, {
            accept: 'text/html,application/xhtml+xml',
            'accept-language': 'nl-NL,nl;q=0.9',
            'user-agent': 'SMV Playwright teaser',
        });
        if (response.status !== 200) {
            throw new Error(`Could not prepare public demo page ${absoluteUrl}: HTTP ${response.status}.`);
        }

        const html = withoutDebugbar(response.body.toString('utf8'));
        documents.set(absoluteUrl, {
            body: Buffer.from(html),
            headers: {
                'content-type': response.headers['content-type'] ?? 'text/html; charset=UTF-8',
            },
            html,
            status: response.status,
        });

        return html;
    };

    await cacheDocument('/');
    const vacancyIndex = await cacheDocument('/vacatures');
    const vacancyHref = firstMatchingHref(vacancyIndex, /href="([^"]*\/vacatures\/[^"?#/]+)"/i);
    if (!vacancyHref) {
        throw new Error('No public Vacancy with suitable demo content was found.');
    }

    const vacancyDetail = await cacheDocument(vacancyHref);
    const preferredCompanyHref = firstMatchingHref(vacancyDetail, /href="([^"]*\/bedrijven\/[^"?#/]+)"/i);
    if (!preferredCompanyHref) {
        throw new Error('The selected public Vacancy has no public Company link.');
    }

    const companyIndex = await cacheDocument('/bedrijven');
    await cacheDocument(preferredCompanyHref);

    const categoryHref = firstMatchingHref(companyIndex, /href="([^"]*\/bedrijven\?[^"#]*category=[^"]+)"/i);
    if (categoryHref) {
        const categoryPage = await cacheDocument(categoryHref);
        const categoryCompanyHref = firstMatchingHref(categoryPage, /href="([^"]*\/bedrijven\/[^"?#/]+)"/i);
        if (categoryCompanyHref) {
            await cacheDocument(categoryCompanyHref);
        }
    }

    return documents;
}

async function cinematicScroll(page, targetY, duration) {
    await page.evaluate(
        ({ destination, milliseconds }) => new Promise((resolve) => {
            const startY = window.scrollY;
            const distance = destination - startY;
            const startedAt = performance.now();
            const easeInOutCubic = (progress) => progress < 0.5
                ? 4 * progress ** 3
                : 1 - ((-2 * progress + 2) ** 3) / 2;

            const frame = (now) => {
                const progress = Math.min((now - startedAt) / milliseconds, 1);
                window.scrollTo(0, startY + distance * easeInOutCubic(progress));

                if (progress < 1) {
                    window.requestAnimationFrame(frame);
                    return;
                }

                resolve();
            };

            window.requestAnimationFrame(frame);
        }),
        { destination: targetY, milliseconds: duration },
    );
}

async function scrollElementIntoFrame(page, locator, duration, offset = 110) {
    await locator.waitFor({ state: 'visible' });
    const targetY = await locator.evaluate((element, topOffset) => (
        window.scrollY + element.getBoundingClientRect().top - topOffset
    ), offset);

    await cinematicScroll(page, Math.max(0, targetY), duration);
}

async function waitForVisibleImages(page) {
    try {
        await page.waitForFunction(() => {
            const images = [...document.images].filter((image) => {
                const rectangle = image.getBoundingClientRect();

                return rectangle.bottom >= 0
                    && rectangle.top <= window.innerHeight
                    && rectangle.right >= 0
                    && rectangle.left <= window.innerWidth;
            });

            return images.every((image) => image.complete && image.naturalWidth > 0);
        }, null, { timeout: 10000 });
    } catch (error) {
        const unavailableImages = await page.evaluate(() => [...document.images]
            .filter((image) => image.complete && image.naturalWidth === 0)
            .map((image) => image.currentSrc || image.src));
        throw new Error(`Visible image readiness failed: ${unavailableImages.join(', ') || 'an image remained pending'}`, { cause: error });
    }
}

async function waitForScene(page, heading, { fonts = false } = {}) {
    await page.waitForLoadState('domcontentloaded');
    await page.getByRole('heading', { name: heading }).first().waitFor({ state: 'visible' });
    if (fonts) {
        await page.evaluate(() => document.fonts?.ready);
    }
    await waitForVisibleImages(page);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    if (overflow > 2) {
        throw new Error(`The scene has ${overflow}px horizontal overflow.`);
    }
}

async function followPublicLink(page, locator) {
    const href = await locator.getAttribute('href');
    if (!href) {
        throw new Error('A public demo link has no navigation destination.');
    }

    try {
        await page.goto(new URL(href, page.url()).href, {
            waitUntil: 'domcontentloaded',
            timeout: 15000,
        });
    } catch (error) {
        const pending = [...pendingRequests.values()]
            .map((request) => `${request.type}: ${request.url}`)
            .join('\n');
        throw new Error(`${error.message}\nPending requests:\n${pending || 'none'}`, { cause: error });
    }
}

async function requireCards(locator, type) {
    if (await locator.count() === 0) {
        throw new Error(`No public ${type} with suitable demo content was found.`);
    }
}

async function moveCursorAside(page) {
    await page.mouse.move(viewport.width - 18, Math.round(viewport.height / 2));
}

await mkdir(artifactDirectory, { recursive: true });
await rm(rawVideoDirectory, { recursive: true, force: true });
await rm(finalVideoPath, { force: true });

const fontCache = await prepareFontCache();
const publicDocuments = await preparePublicJourneyDocuments(baseUrl);
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    viewport,
    deviceScaleFactor: 1,
    locale: 'nl-NL',
    timezoneId: 'Europe/Amsterdam',
    colorScheme: 'light',
    reducedMotion: 'no-preference',
    recordVideo: {
        dir: rawVideoDirectory,
        size: viewport,
    },
});

await context.route('https://fonts.googleapis.com/**', async (route) => {
    await route.fulfill({
        body: fontCache.stylesheet,
        contentType: 'text/css; charset=utf-8',
        status: 200,
    });
});
await context.route('https://fonts.gstatic.com/**', async (route) => {
    const font = fontCache.fonts.get(route.request().url());
    if (!font) {
        await route.abort('failed');
        return;
    }

    await route.fulfill({
        body: font,
        contentType: 'font/woff2',
        status: 200,
    });
});
await context.route(`${baseUrl}/**`, async (route) => {
    const request = route.request();
    if (!['GET', 'HEAD'].includes(request.method())) {
        await route.continue();
        return;
    }

    if (new URL(request.url()).pathname.startsWith('/_debugbar/')) {
        await route.abort('blockedbyclient');
        return;
    }

    const requestUrl = new URL(request.url());
    if (request.resourceType() === 'document') {
        const document = publicDocuments.get(requestUrl.href);
        if (!document) {
            throw new Error(`Public demo page was not prepared: ${requestUrl.href}`);
        }

        await route.fulfill({
            body: document.body,
            headers: document.headers,
            status: document.status,
        });
        return;
    }

    if (requestUrl.pathname.startsWith('/build/')) {
        const buildDirectory = path.join(root, 'public', 'build');
        const assetPath = path.resolve(root, 'public', `.${decodeURIComponent(requestUrl.pathname)}`);
        const normalizedAssetPath = process.platform === 'win32' ? assetPath.toLowerCase() : assetPath;
        const normalizedBuildDirectory = process.platform === 'win32' ? buildDirectory.toLowerCase() : buildDirectory;
        if (!normalizedAssetPath.startsWith(`${normalizedBuildDirectory}${path.sep}`)) {
            await route.abort('accessdenied');
            return;
        }

        const contentTypes = {
            '.css': 'text/css; charset=utf-8',
            '.js': 'text/javascript; charset=utf-8',
            '.png': 'image/png',
            '.svg': 'image/svg+xml',
            '.webp': 'image/webp',
        };
        await route.fulfill({
            body: await readFile(assetPath),
            contentType: contentTypes[path.extname(assetPath).toLowerCase()] ?? 'application/octet-stream',
            headers: { 'cache-control': 'public, max-age=31536000, immutable' },
            status: 200,
        });
        return;
    }

    let response;
    try {
        response = await requestPublicAsset(request.url(), {
            accept: request.headers().accept ?? '*/*',
            'accept-language': 'nl-NL,nl;q=0.9',
            'user-agent': request.headers()['user-agent'] ?? 'SMV Playwright teaser',
        });
    } catch (error) {
        throw new Error(`Demo asset fetch failed for ${request.resourceType()} ${request.url()}`, { cause: error });
    }
    const headers = response.headers;
    delete headers['content-encoding'];
    delete headers['content-length'];
    delete headers['transfer-encoding'];
    await route.fulfill({
        body: response.body,
        headers,
        status: response.status,
    });
});

// Local debug tooling stays enabled for development, but never enters the marketing recording.
await context.addInitScript(() => {
    const installDemoStyles = () => {
        if (!document.documentElement || document.querySelector('[data-smv-demo-style]')) {
            return;
        }

        const style = document.createElement('style');
        style.dataset.smvDemoStyle = 'true';
        style.textContent = `
            #phpdebugbar,
            .phpdebugbar,
            [id^="phpdebugbar"],
            [class^="phpdebugbar"] {
                display: none !important;
                visibility: hidden !important;
            }
        `;
        document.documentElement.append(style);
    };

    installDemoStyles();
    new MutationObserver(installDemoStyles).observe(document, { childList: true, subtree: true });
});

// Warm the Laravel response, compiled CSS and remote font cache before the recorded page exists.
const warmupPage = await context.newPage();
await warmupPage.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 60000 });
await warmupPage.evaluate(() => document.fonts?.ready);
await warmupPage.close();

const page = await context.newPage();
const video = page.video();
const browserErrors = [];
const pendingRequests = new Map();
page.on('request', (request) => pendingRequests.set(request, {
    type: request.resourceType(),
    url: request.url(),
}));
page.on('requestfinished', (request) => pendingRequests.delete(request));
page.on('requestfailed', (request) => pendingRequests.delete(request));
page.on('pageerror', (error) => browserErrors.push(error.message));
page.on('console', (message) => {
    if (message.type() === 'error') {
        browserErrors.push(message.text());
    }
});

const startedAt = performance.now();
const sceneTimes = [];
let recordingError = null;
const markScene = (name) => sceneTimes.push({
    name,
    seconds: (performance.now() - startedAt) / 1000,
});

try {
    await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });
    await waitForScene(page, 'Sales- en marketingvacatures zonder de ruis', { fonts: true });
    await moveCursorAside(page);
    markScene('Homepage hero');
    await pause(timing.openingBeat);

    const companyBanner = page.locator('[data-company-logo-banner]');
    await companyBanner.waitFor({ state: 'visible' });
    await scrollElementIntoFrame(page, companyBanner, timing.homeScroll, 55);
    await waitForVisibleImages(page);
    await moveCursorAside(page);
    markScene('Homepage company banner');
    await pause(timing.bannerBeat);

    const publicNavigation = page.getByRole('navigation', { name: 'Hoofdnavigatie' });
    await followPublicLink(
        page,
        publicNavigation.getByRole('link', { name: 'Vacatures', exact: true }),
    );
    await waitForScene(page, 'Vind jouw volgende vacature');
    await moveCursorAside(page);
    markScene('Vacancy discovery');
    await pause(timing.discoveryBeat);

    let vacancyCards = page.locator('[data-demo="vacancy-card"]');
    await requireCards(vacancyCards, 'Vacancy');
    const selectedVacancyCard = vacancyCards.first();
    await scrollElementIntoFrame(page, selectedVacancyCard, timing.discoveryScroll, 170);
    await waitForVisibleImages(page);
    await selectedVacancyCard.hover();
    markScene('Vacancy cards');
    await pause(timing.cardBeat);

    const vacancyLink = selectedVacancyCard.locator('h2 a').first();
    const vacancyTitle = (await vacancyLink.textContent())?.trim();
    if (!vacancyTitle) {
        throw new Error('The selected public Vacancy has no readable title.');
    }

    await followPublicLink(page, vacancyLink);
    await waitForScene(page, vacancyTitle);
    const companyLink = page.locator('a[href*="/bedrijven/"]').first();
    const preferredCompanyHref = await companyLink.getAttribute('href');
    await moveCursorAside(page);
    markScene('Vacancy detail');
    await pause(timing.detailBeat);

    await scrollElementIntoFrame(
        page,
        page.getByRole('heading', { name: 'Over deze vacature' }),
        timing.detailScroll,
        105,
    );
    await moveCursorAside(page);
    await pause(timing.detailScrollBeat);

    await followPublicLink(
        page,
        page.getByRole('navigation', { name: 'Hoofdnavigatie' })
            .getByRole('link', { name: 'Bedrijven', exact: true }),
    );
    await waitForScene(page, 'Ontdek bedrijven');
    await moveCursorAside(page);
    markScene('Company discovery');
    await pause(timing.companyDiscoveryBeat);

    let companyCards = page.locator('[data-demo="company-card"]');
    await requireCards(companyCards, 'Company');
    await scrollElementIntoFrame(page, companyCards.first(), timing.companyDiscoveryScroll, 175);
    await waitForVisibleImages(page);
    markScene('Company cards');
    await pause(timing.companyCardsBeat);

    const categoryLinks = page
        .getByRole('navigation', { name: 'Bedrijfscategorieën' })
        .getByRole('link')
        .filter({ hasNotText: 'Alle bedrijven' });

    if (await categoryLinks.count() > 0) {
        await followPublicLink(page, categoryLinks.first());
        await waitForScene(page, 'Ontdek bedrijven');
        companyCards = page.locator('[data-demo="company-card"]');
        await requireCards(companyCards, 'Company');
        await scrollElementIntoFrame(page, companyCards.first(), 1500, 175);
        await waitForVisibleImages(page);
        markScene('Company category');
        await pause(timing.categoryBeat);
    }

    const preferredCompanyCard = preferredCompanyHref
        ? companyCards.filter({ has: page.locator(`a[href="${preferredCompanyHref}"]`) }).first()
        : null;
    const selectedCompanyCard = preferredCompanyCard && await preferredCompanyCard.count() > 0
        ? preferredCompanyCard
        : companyCards.first();
    const selectedCompanyLink = selectedCompanyCard.locator('h2 a').first();
    const companyName = (await selectedCompanyLink.textContent())?.trim();
    if (!companyName) {
        throw new Error('The selected public Company has no readable name.');
    }

    await selectedCompanyCard.hover();
    await pause(500);
    await followPublicLink(page, selectedCompanyLink);
    await waitForScene(page, companyName);
    await moveCursorAside(page);
    markScene('Company detail');
    await pause(timing.companyDetailBeat);

    const companyDescriptionHeading = page.getByRole('heading', { name: `Over ${companyName}` });
    if (await companyDescriptionHeading.count() > 0) {
        await scrollElementIntoFrame(page, companyDescriptionHeading, timing.companyDetailScroll, 115);
    } else {
        await cinematicScroll(page, Math.min(520, await page.evaluate(() => document.body.scrollHeight)), timing.companyDetailScroll);
    }
    await waitForVisibleImages(page);
    await moveCursorAside(page);
    markScene('Company detail ending');
    await pause(timing.endingBeat);

    if (browserErrors.length > 0) {
        throw new Error(`Browser errors were captured:\n${browserErrors.join('\n')}`);
    }
} catch (error) {
    recordingError = error;
} finally {
    await context.close();
}

if (!recordingError && !video) {
    recordingError = new Error('Playwright did not create a video artifact.');
}

if (!recordingError) {
    await video.saveAs(rawVideoPath);
}

await browser.close();

if (recordingError) {
    throw recordingError;
}

const duration = ((performance.now() - startedAt) / 1000) - openingTrimSeconds;
await trimOpeningFrames(rawVideoPath, finalVideoPath);
await rm(rawVideoDirectory, { recursive: true, force: true });

console.log(`SMV teaser saved to ${finalVideoPath}`);
console.log(`Recorded journey duration after opening trim: ${duration.toFixed(1)} seconds`);
console.table(sceneTimes.map((scene) => ({
    scene: scene.name,
    startsAtSeconds: scene.seconds.toFixed(1),
})));

if (duration < 30 || duration > 40) {
    throw new Error(`The teaser duration is ${duration.toFixed(1)}s; expected 30–40s.`);
}
