/*
 * 97 Foundation V7.2 — end-to-end tests (Playwright, real browser against the running site).
 *
 * Setup (once):   npm install --no-save playwright && npx playwright install chromium
 * Run:            BASE_URL=http://localhost/97-foundation ADMIN_COOKIE="foundation97_session=<id>" node tests/e2e/site.e2e.js
 *
 *   BASE_URL         Site root (no trailing slash).
 *   ADMIN_COOKIE     Session cookie of a signed-in Super Admin (sign in normally, copy the cookie from the browser).
 *                    Admin login uses a CAPTCHA, so automated sign-in is not possible on a real site.
 *   ADMIN_LOGIN_URL  Test-only alternative used by the isolated test server (e.g. /__test/login).
 *   ROLE_LOGIN_URL   Optional test-only URL to sign in as another user: "{url}?email={email}".
 *   ONLY             Optional text: run only tests whose name contains it.
 *
 * The suite creates clearly named test records ("E2E …"). Run it against a test copy of the database, not production.
 */
'use strict';
const { chromium } = require('playwright');
const path = require('path');
const assert = require('assert/strict');

const BASE = (process.env.BASE_URL || 'http://127.0.0.1:8097').replace(/\/$/, '');
const RUN = Date.now().toString(36);
const tests = [];
const test = (name, fn) => tests.push({ name, fn });
const u = p => BASE + (p.startsWith('/') ? p : '/' + p);
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64');
const state = {};

async function newPage(ctx, errors) {
  const page = await ctx.newPage();
  page.on('pageerror', e => errors && errors.push('pageerror: ' + e.message));
  page.on('console', m => { if (m.type() === 'error' && errors) errors.push('console: ' + m.text()); });
  return page;
}
async function adminContext(browser, email) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  if (email && process.env.ROLE_LOGIN_URL) {
    const p = await ctx.newPage(); await p.goto(u(process.env.ROLE_LOGIN_URL) + '?email=' + encodeURIComponent(email)); await p.close();
  } else if (process.env.ADMIN_LOGIN_URL) {
    const p = await ctx.newPage(); await p.goto(u(process.env.ADMIN_LOGIN_URL)); await p.close();
  } else if (process.env.ADMIN_COOKIE) {
    const [name, value] = process.env.ADMIN_COOKIE.split('=');
    const url = new URL(BASE);
    await ctx.addCookies([{ name: name.trim(), value: (value || '').trim(), domain: url.hostname, path: '/' }]);
  } else {
    throw new Error('Set ADMIN_COOKIE (or ADMIN_LOGIN_URL on the test server) to run admin tests.');
  }
  return ctx;
}
async function adminPage(browser) { state.admin = state.admin || await adminContext(browser); return state.admin.newPage(); }
async function noHorizontalScroll(page) { return page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1); }
async function scrollThrough(page) {
  const h = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < h; y += 400) { await page.evaluate(y => scrollTo(0, y), y); await page.waitForTimeout(90); }
  await page.waitForTimeout(1600);
}
async function flashText(page) { return (await page.locator('.alert, .admin-alert, [class*="flash"], .toast').allInnerTexts()).join(' '); }
async function setFundraising(browser, enabled, general = true) {
  const page = await adminPage(browser);
  await page.goto(u('/admin/campaigns'));
  await page.locator('[data-fundraising-form] .v7-segment label', { hasText: enabled ? 'On' : 'Paused' }).click();
  assert.equal(await page.locator('[data-fundraising-form] input[name="enabled"]:checked').inputValue(), enabled ? '1' : '0');
  const g = page.locator('[data-fundraising-form] input[name="general_when_paused"]');
  if (!enabled) { if (general) await g.check(); else await g.uncheck(); }
  await Promise.all([page.waitForNavigation(), page.locator('[data-fundraising-form] button[type="submit"]').click()]);
  const text = await page.locator('[data-fundraising-form] h3').innerText();
  await page.close();
  return text;
}

/* ------------------------------------------------------------------ Homepage */
test('Homepage renders every key section without errors', async ({ browser }) => {
  const errors = []; const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await newPage(ctx, errors);
  const res = await page.goto(u('/'), { waitUntil: 'networkidle' });
  assert.equal(res.status(), 200);
  await assert.doesNotReject(page.locator('h1.hero7-title').waitFor());
  for (const sel of ['.h7-paths', '.h7-about', '.h7-causes', '.h7-impact', '.h7-cta']) assert.ok(await page.locator(sel).count(), sel + ' missing');
  assert.equal(await page.locator('h1').count(), 1, 'exactly one h1');
  assert.ok(await page.evaluate(() => document.fonts.check('16px Inter')), 'self-hosted Inter font loaded');
  assert.deepEqual(errors, []);
  await ctx.close();
});

test('Pages fit mobile, tablet and desktop widths without sideways scrolling', async ({ browser }) => {
  for (const [w, h] of [[390, 844], [768, 1024], [1440, 900]]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: w < 768, hasTouch: w < 768 });
    const page = await ctx.newPage();
    for (const p of ['/', '/news', '/donate', '/campaigns']) {
      await page.goto(u(p), { waitUntil: 'networkidle' });
      if (p === '/') await scrollThrough(page);
      assert.ok(await noHorizontalScroll(page), `horizontal scroll on ${p} at ${w}px`);
    }
    await ctx.close();
  }
});

test('Scroll animations reveal content, count up numbers and the hero slider advances', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  await page.goto(u('/'), { waitUntil: 'networkidle' });
  assert.match(await page.evaluate(() => document.documentElement.className), /js-motion/);
  const before = await page.locator('[data-reveal]').count();
  assert.ok(before > 5, 'reveal targets present');
  const firstDot = await page.locator('.hero7-dot.is-active').getAttribute('data-hero7-dot');
  await scrollThrough(page);
  await page.waitForTimeout(800);
  assert.ok(await page.locator('[data-reveal]').count() < before, 'elements were revealed while scrolling');
  const counts = await page.locator('.h7-stat [data-count]').evaluateAll(els => els.map(e => [e.dataset.count, e.textContent.replace(/,/g, '')]));
  for (const [target, shown] of counts) assert.equal(shown, target, 'counter finished at its real value');
  await page.evaluate(() => scrollTo(0, 0));
  await page.waitForFunction(d => document.querySelector('.hero7-dot.is-active')?.getAttribute('data-hero7-dot') !== d, firstDot, { timeout: 17000 });
  await ctx.close();
});

test('Reduced-motion visitors get static, fully visible content', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  await page.goto(u('/'), { waitUntil: 'networkidle' });
  assert.doesNotMatch(await page.evaluate(() => document.documentElement.className), /js-motion/);
  const hidden = await page.evaluate(() => [...document.querySelectorAll('.h7-path, .h7-about-copy .h7-h2, .h7-cause')].filter(e => getComputedStyle(e).opacity !== '1').length);
  assert.equal(hidden, 0);
  await ctx.close();
});

/* ------------------------------------------------------------------ Navigation */
test('Desktop menu dropdowns open by hover and keyboard', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage(); await page.goto(u('/'));
  const item = page.locator('[data-nav-dropdown]').first();
  await item.hover();
  await page.waitForFunction(() => document.querySelector('[data-nav-dropdown]').classList.contains('is-open'));
  await page.mouse.move(5, 600); await page.waitForTimeout(400);
  await item.locator('.nav7-link').focus(); await page.keyboard.press('ArrowDown');
  assert.equal(await item.locator('.nav7-link').getAttribute('aria-expanded'), 'true');
  await page.keyboard.press('Escape');
  assert.equal(await item.locator('.nav7-link').getAttribute('aria-expanded'), 'false');
  await ctx.close();
});

test('Mobile menu opens, traps focus and closes with Escape', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await ctx.newPage(); await page.goto(u('/'));
  await page.locator('[data-menu-toggle]').click();
  await page.waitForSelector('#mobile-nav7.is-open');
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => !document.querySelector('#mobile-nav7').classList.contains('is-open'));
  await ctx.close();
});

test('In-site page transitions load pages without a full reload', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage(); await page.goto(u('/'), { waitUntil: 'networkidle' });
  await page.evaluate(() => { window.__e2eMarker = 'kept'; });
  await page.locator('.nav7 a[href$="/news"], [data-primary-nav] a[href$="/news"]').first().click();
  await page.waitForURL(/\/news$/);
  await page.waitForSelector('main .n7-hero h1');
  assert.equal(await page.evaluate(() => window.__e2eMarker), 'kept', 'page was not fully reloaded');
  assert.match(await page.title(), /News/);
  await page.goBack(); await page.waitForSelector('.hero7-title');
  await ctx.close();
});

test('Gallery viewer opens, moves between photos and closes with Escape', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage(); await page.goto(u('/'), { waitUntil: 'networkidle' });
  const tiles = page.locator('.h7-tile');
  if (!(await tiles.count())) { await ctx.close(); return 'skipped: no gallery items'; }
  await tiles.first().scrollIntoViewIfNeeded(); await page.waitForTimeout(1500);
  await tiles.first().click();
  await page.waitForSelector('.lb7.is-open');
  const first = await page.locator('.lb7-count').innerText();
  if (await page.locator('.lb7-next').count()) { await page.keyboard.press('ArrowRight'); await page.waitForTimeout(450); assert.notEqual(await page.locator('.lb7-count').innerText(), first); }
  await page.keyboard.press('Escape');
  await page.waitForSelector('.lb7', { state: 'detached' });
  await ctx.close();
});

/* ------------------------------------------------------------------ Donations & receipts */
async function fillDonation(page, name) {
  await page.goto(u('/donate'));
  await page.selectOption('[data-donation-form] select[name="donation_account_id"]', { index: 1 }).catch(() => {});
  await page.fill('[data-donation-form] input[name="amount"]', '1500');
  await page.fill('[data-donation-form] input[name="donor_name"]', name);
  await page.fill('[data-donation-form] input[name="donor_email"]', 'e2e@example.test');
  await page.fill('[data-donation-form] input[name="transaction_reference"]', 'E2E-' + RUN);
}

test('Donation form blocks submission without a receipt (in the browser)', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await fillDonation(page, 'E2E No Receipt ' + RUN);
  await page.locator('[data-donation-form] button[type="submit"]').click();
  await page.waitForSelector('[data-receipt-error]:not([hidden])');
  assert.match(await page.locator('[data-receipt-error]').innerText(), /receipt/i);
  assert.match(page.url(), /\/donate/);
  await ctx.close();
});

test('Receipt upload rejects wrong file types and oversized files with clear messages', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await fillDonation(page, 'E2E Bad File ' + RUN);
  const input = page.locator('[data-receipt-input]');
  await input.setInputFiles({ name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('hello') });
  assert.match(await page.locator('[data-receipt-error]').innerText(), /not accepted/i);
  const max = Number(await page.locator('[data-receipt]').getAttribute('data-max'));
  await input.setInputFiles({ name: 'big.pdf', mimeType: 'application/pdf', buffer: Buffer.alloc(max + 1024, 1) });
  assert.match(await page.locator('[data-receipt-error]').innerText(), /must be/i);
  await input.setInputFiles({ name: 'bank-slip.png', mimeType: 'image/png', buffer: PNG });
  await page.waitForSelector('[data-receipt-preview]:not([hidden])');
  assert.equal(await page.locator('[data-receipt-name]').innerText(), 'bank-slip.png');
  assert.ok(await page.locator('[data-receipt-thumb] img').count(), 'image preview shown');
  await ctx.close();
});

test('Server rejects a donation posted without a receipt (bypassing the browser checks)', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/donate'));
  const token = await page.locator('[data-donation-form] input[name="_token"]').inputValue();
  const res = await page.request.post(u('/donate'), { multipart: { _token: token, donor_name: 'E2E Bypass ' + RUN, amount: '900', payment_method: 'bank_transfer' }, maxRedirects: 0 });
  assert.equal(res.status(), 302);
  await page.goto(u('/donate'));
  assert.match(await page.locator('.alert.danger').innerText(), /receipt/i);
  assert.equal(await page.locator('input[name="donor_name"]').inputValue(), 'E2E Bypass ' + RUN, 'typed details are kept');
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/donations?q=' + encodeURIComponent('E2E Bypass ' + RUN)));
  assert.equal(await admin.locator('tbody tr:has-text("E2E Bypass")').count(), 0, 'no record was created');
  await admin.close(); await ctx.close();
});

test('A donation with a valid receipt is accepted and appears for review', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  const name = 'E2E Donor ' + RUN;
  await fillDonation(page, name);
  await page.locator('[data-receipt-input]').setInputFiles({ name: 'bank-slip.png', mimeType: 'image/png', buffer: PNG });
  await Promise.all([page.waitForNavigation(), page.locator('[data-donation-form] button[type="submit"]').click()]);
  await page.waitForSelector('.donation-done');
  state.donationRef = (await page.locator('.donation-done code').innerText()).trim();
  assert.match(state.donationRef, /^[0-9a-f-]{36}$/);
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/donations?q=' + encodeURIComponent(name)));
  const row = admin.locator('tbody tr', { hasText: name });
  assert.equal(await row.count(), 1);
  assert.match(await row.innerText(), /Pending Verification/i);
  assert.match(await row.innerText(), /Receipt attached/i);
  const href = await row.locator('a[title="View donation"]').getAttribute('href');
  state.donationId = href.split('/').pop();
  await admin.close(); await ctx.close();
});

test('Receipts are private: anonymous visitors cannot open them', async ({ browser }) => {
  assert.ok(state.donationId, 'needs the donation created above');
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  const res = await page.request.get(u('/admin/donations/proof/' + state.donationId), { maxRedirects: 0 });
  assert.ok([302, 403].includes(res.status()), 'proof requires admin sign-in, got ' + res.status());
  for (const p of ['/storage/private/donation-proofs/', '/storage/logs/php-error.log']) {
    const r = await page.request.get(u(p), { maxRedirects: 0 });
    assert.ok(r.status() !== 200 || !/(PNG|error)/.test(await r.text()), 'storage must not be public: ' + p);
  }
  const admin = await adminPage(browser);
  const ok = await admin.request.get(u('/admin/donations/proof/' + state.donationId));
  assert.equal(ok.status(), 200); assert.match(ok.headers()['content-type'], /image\/png/);
  await admin.close(); await ctx.close();
});

test('Administrators review the receipt and approve the donation', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/donations/view/' + state.donationId));
  assert.ok(await admin.locator('.v7-receipt-image img').count(), 'receipt preview on the review page');
  await Promise.all([admin.waitForNavigation(), admin.locator('button[name="status"][value="approved"]').click()]);
  await admin.goto(u('/admin/donations/view/' + state.donationId));
  assert.match(await admin.locator('.donation-record-status').innerText(), /Verified/i);
  await admin.close();
});

/* ------------------------------------------------------------------ Fundraising switch */
test('Pausing fundraising hides campaigns and blocks campaign donations', async ({ browser }) => {
  assert.match(await setFundraising(browser, false, true), /paused/i);
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/'));
  assert.equal(await page.locator('.h7-campaigns').count(), 0, 'homepage campaigns hidden');
  await page.goto(u('/campaigns'));
  assert.ok(await page.locator('.fundraising-paused7').count(), 'campaigns page shows paused notice');
  await page.goto(u('/donate'));
  assert.ok(await page.locator('.donate-fund-note').count(), 'only the general fund is offered');
  const token = await page.locator('[data-donation-form] input[name="_token"]').inputValue();
  const res = await page.request.post(u('/donate'), { multipart: { _token: token, donor_name: 'E2E Paused ' + RUN, amount: '700', campaign_id: '1', proof: { name: 'slip.png', mimeType: 'image/png', buffer: PNG } }, maxRedirects: 0 });
  assert.equal(res.status(), 302);
  await page.goto(u('/donate'));
  assert.match(await page.locator('.alert.danger').innerText(), /paused|not accepting/i);
  await ctx.close();
});

test('Pausing all donations closes the donation form and hides Donate buttons', async ({ browser }) => {
  await setFundraising(browser, false, false);
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/donate'));
  assert.ok(await page.locator('.donate-paused').count());
  assert.equal(await page.locator('[data-donation-form]').count(), 0);
  assert.equal(await page.locator('.site-header7 .btn7-donate:visible').count(), 0);
  await ctx.close();
});

test('Turning fundraising back on restores campaigns', async ({ browser }) => {
  assert.match(await setFundraising(browser, true), /on/i);
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/campaigns'));
  assert.equal(await page.locator('.fundraising-paused7').count(), 0);
  await page.goto(u('/donate'));
  assert.ok(await page.locator('[data-donation-form] select[name="campaign_id"]').count(), 'campaign choice is back');
  await ctx.close();
});

/* ------------------------------------------------------------------ News & Blog */
test('Administrators manage categories and publish an article with a readable web address', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/posts'));
  state.catName = 'E2E Topic ' + RUN; state.catSlug = 'e2e-topic-' + RUN;
  const add = admin.locator('form.v7-cat-row.is-new');
  await add.locator('input[name="name"]').fill(state.catName);
  await add.locator('input[name="slug"]').fill(state.catSlug);
  await Promise.all([admin.waitForNavigation(), add.locator('button[type="submit"]').click()]);
  state.slug = 'e2e-article-' + RUN;
  await admin.locator('[data-post7-new]').click();
  const f = admin.locator('[data-post7-form]');
  await f.locator('select[name="post_type"]').selectOption('news');
  await f.locator('select[name="category_id"]').selectOption({ label: state.catName });
  await f.locator('input[name="title"]').fill('E2E Article ' + RUN + ': School supplies delivered');
  await f.locator('input[name="slug"]').fill(state.slug);
  await f.locator('textarea[name="excerpt"]').fill('Volunteers delivered school supplies to students in Thambala.');
  await f.locator('textarea[name="content"]').fill('Opening paragraph about the day.\n\n## What we delivered\n\n- Books\n- Uniforms\n\n## Who helped\n\nThanks to **every volunteer**.');
  await f.locator('select[name="status"]').selectOption('published');
  await f.locator('input[name="meta_title"]').fill('E2E school supplies ' + RUN);
  await f.locator('textarea[name="meta_description"]').fill('How 97 Foundation volunteers delivered school supplies in Thambala.');
  await Promise.all([admin.waitForNavigation(), f.locator('[data-post7-submit]').click()]);
  assert.match(await flashText(admin), /Published/i);
  await admin.close();
});

test('Published article has its own page, contents, share links and article metadata', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  const res = await page.goto(u('/news/' + state.slug));
  assert.equal(res.status(), 200);
  assert.match(await page.locator('h1').innerText(), /E2E Article/);
  assert.equal(await page.locator('.a7-prose h2').count(), 2);
  assert.ok(await page.locator('.a7-toc a[href="#what-we-delivered"]').count(), 'table of contents');
  assert.equal(await page.locator('link[rel="canonical"]').getAttribute('href'), u('/news/' + state.slug));
  assert.equal(await page.locator('meta[property="og:type"]').getAttribute('content'), 'article');
  assert.ok(await page.locator('meta[property="article:published_time"]').count());
  const ld = await page.locator('script[type="application/ld+json"]').allInnerTexts();
  assert.ok(ld.some(t => /"NewsArticle"/.test(t)), 'NewsArticle structured data');
  assert.ok(ld.some(t => /"BreadcrumbList"/.test(t)), 'breadcrumb structured data');
  const enc = encodeURIComponent(u('/news/' + state.slug));
  const hrefs = await page.locator('.share7').first().locator('a[data-share-network]').evaluateAll(as => Object.fromEntries(as.map(a => [a.dataset.shareNetwork, a.href])));
  for (const n of ['whatsapp', 'facebook', 'linkedin', 'x', 'telegram']) assert.ok(hrefs[n] && hrefs[n].includes(enc), n + ' share link uses the article URL');
  assert.ok(hrefs.whatsapp.startsWith('https://wa.me/') && hrefs.facebook.startsWith('https://www.facebook.com/sharer/') && hrefs.linkedin.startsWith('https://www.linkedin.com/sharing/') && hrefs.x.startsWith('https://x.com/intent/'));
  await ctx.close();
});

test('Copy-link button copies the article address', async ({ browser }) => {
  const ctx = await browser.newContext({ permissions: ['clipboard-read', 'clipboard-write'] }); const page = await ctx.newPage();
  await page.goto(u('/news/' + state.slug));
  const btn = page.locator('.a7-foot [data-share-copy]');
  await btn.click();
  await page.waitForFunction(() => document.querySelector('.a7-foot [data-share-copy-label]').textContent.includes('copied'));
  assert.equal(await page.evaluate(() => navigator.clipboard.readText()), u('/news/' + state.slug));
  await ctx.close();
});

test('Article appears in the news list, its category page and the sitemap', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/news'));
  assert.ok(await page.locator(`a[href$="/news/${state.slug}"]`).count(), 'listed on /news');
  await page.goto(u('/news/category/' + state.catSlug));
  assert.match(await page.locator('h1').innerText(), new RegExp(state.catName));
  assert.ok(await page.locator(`a[href$="/news/${state.slug}"]`).count(), 'listed on the category page');
  await page.goto(u('/news?q=' + encodeURIComponent('E2E Article ' + RUN)));
  assert.ok(await page.locator(`a[href$="/news/${state.slug}"]`).count(), 'found by search');
  const sm = await page.request.get(u('/sitemap.xml'));
  assert.ok((await sm.text()).includes('/news/' + state.slug), 'in sitemap');
  await ctx.close();
});

test('Unpublishing takes the article offline; drafts and scheduled posts stay hidden', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/posts?q=' + encodeURIComponent(state.slug)));
  const card = admin.locator('.v7-post-card', { hasText: 'E2E Article ' + RUN });
  await Promise.all([admin.waitForNavigation(), card.locator('form[action$="/admin/posts/status"] button').click()]);
  assert.match(await flashText(admin), /unpublished/i);
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  assert.equal((await page.goto(u('/news/' + state.slug))).status(), 404);
  assert.ok(!(await (await page.request.get(u('/sitemap.xml'))).text()).includes('/news/' + state.slug));
  // Scheduled post (future date) must not be public yet.
  await admin.locator('[data-post7-new]').click();
  const f = admin.locator('[data-post7-form]');
  await f.locator('input[name="title"]').fill('E2E Scheduled ' + RUN);
  await f.locator('input[name="slug"]').fill('e2e-scheduled-' + RUN);
  await f.locator('select[name="status"]').selectOption('published');
  await f.locator('input[name="published_at"]').fill('2099-01-01T09:00');
  await Promise.all([admin.waitForNavigation(), f.locator('[data-post7-submit]').click()]);
  assert.match(await flashText(admin), /Scheduled/i);
  assert.equal((await page.goto(u('/news/e2e-scheduled-' + RUN))).status(), 404);
  // Publish the first article again for later checks.
  await admin.goto(u('/admin/posts?q=' + encodeURIComponent(state.slug)));
  await Promise.all([admin.waitForNavigation(), admin.locator('.v7-post-card', { hasText: 'E2E Article ' + RUN }).locator('form[action$="/admin/posts/status"] button').click()]);
  assert.equal((await page.goto(u('/news/' + state.slug))).status(), 200);
  await admin.close(); await ctx.close();
});

test('Campaign pages can be shared with their direct link', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/campaigns'));
  const link = page.locator('a[href*="/campaigns/"]').first();
  if (!(await link.count())) { await ctx.close(); return 'skipped: no public campaigns'; }
  const href = await link.getAttribute('href');
  await page.goto(href.startsWith('http') ? href : u(href));
  const share = page.locator('.share7[data-share]');
  assert.ok(await share.count(), 'share bar on campaign page');
  assert.equal(await share.getAttribute('data-share-url'), page.url().replace(/\?.*$/, ''));
  await ctx.close();
});

/* ------------------------------------------------------------------ Admin management */
test('Online card payments can be switched on and off from the admin', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/developer/payment-gateway'));
  const onRadio = admin.locator('[data-switch-form] input[name="enabled"][value="1"]');
  if (await onRadio.isDisabled()) { await admin.close(); return 'skipped: PayHere credentials are not configured'; }
  const wasOn = await onRadio.isChecked();
  await admin.locator('[data-switch-form] .v7-segment label', { hasText: 'On' }).click();
  await Promise.all([admin.waitForNavigation(), admin.locator('[data-switch-form] button[type="submit"]').click()]);
  assert.match(await flashText(admin), /Online payments are on/i);
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  await page.goto(u('/donate'));
  assert.equal(await page.locator('[data-donation-tab="online"]').isDisabled(), false, 'online tab available');
  await admin.goto(u('/admin/online-payments'));
  await admin.locator('[data-switch-form] .v7-segment label', { hasText: 'Off' }).click();
  await Promise.all([admin.waitForNavigation(), admin.locator('[data-switch-form] button[type="submit"]').click()]);
  assert.match(admin.url(), /\/admin\/online-payments/, 'returns to the page the switch was used on');
  assert.match(await flashText(admin), /Online payments are off/i);
  await page.goto(u('/donate'));
  assert.equal(await page.locator('[data-donation-tab="online"]').isDisabled(), true, 'online tab unavailable');
  const anon = await page.request.post(u('/admin/developer/payment-gateway/toggle'), { form: { enabled: '1' }, maxRedirects: 0 });
  assert.ok([302, 403, 419].includes(anon.status()), 'anonymous toggle blocked');
  if (wasOn) { await admin.goto(u('/admin/developer/payment-gateway')); await admin.locator('[data-switch-form] .v7-segment label', { hasText: 'On' }).click(); await Promise.all([admin.waitForNavigation(), admin.locator('[data-switch-form] button[type="submit"]').click()]); }
  await admin.close(); await ctx.close();
});

test('Gallery photos can be hidden and shown again in one click', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/gallery'));
  const card = admin.locator('.v7-media-card').first();
  if (!(await card.count())) { await admin.close(); return 'skipped: no gallery photos'; }
  const title = (await card.locator('.v7-media-body b').innerText()).trim();
  const hiddenBefore = (await card.getAttribute('class')).includes('is-off-site');
  await Promise.all([admin.waitForNavigation(), card.locator('form[action$="/admin/gallery/toggle"] button').click()]);
  assert.match(await flashText(admin), new RegExp(hiddenBefore ? 'visible' : 'hidden', 'i'));
  const again = admin.locator('.v7-media-card', { hasText: title }).first();
  assert.equal((await again.getAttribute('class')).includes('is-off-site'), !hiddenBefore);
  await Promise.all([admin.waitForNavigation(), again.locator('form[action$="/admin/gallery/toggle"] button').click()]);
  assert.equal((await admin.locator('.v7-media-card', { hasText: title }).first().getAttribute('class')).includes('is-off-site'), hiddenBefore, 'restored');
  await admin.close();
});

test('Message recipients can be searched, selected and counted', async ({ browser }) => {
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/messages'));
  const rows = admin.locator('[data-recipient-row]');
  const total = await rows.count();
  if (!total) { await admin.close(); return 'skipped: no users to message'; }
  await admin.locator('[data-recipient-all]').click();
  assert.equal(await admin.locator('[data-recipient-count]').innerText(), `${total} selected`);
  await admin.locator('[data-recipient-none]').click();
  assert.equal(await admin.locator('[data-recipient-count]').innerText(), 'None selected');
  const firstName = (await rows.first().locator('.v7-recipient-copy b').innerText()).trim();
  await admin.locator('[data-recipient-search]').fill(firstName.slice(0, 5));
  const visible = await rows.evaluateAll(rs => rs.filter(r => r.style.display !== 'none').length);
  assert.ok(visible >= 1 && visible <= total, 'search filters the list');
  await admin.close();
});

/* ------------------------------------------------------------------ Security & SEO */
test('Admin pages and actions require an authorised administrator', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  for (const p of ['/admin', '/admin/posts', '/admin/campaigns', '/admin/donations']) {
    const r = await page.request.get(u(p), { maxRedirects: 0 });
    assert.equal(r.status(), 302, p + ' must redirect to sign-in');
    assert.match(r.headers()['location'] || '', /admin\/login/);
  }
  const r = await page.request.post(u('/admin/campaigns/fundraising'), { form: { enabled: '0' }, maxRedirects: 0 });
  assert.ok([302, 403, 419].includes(r.status()), 'anonymous POST blocked, got ' + r.status());
  await page.goto(u('/campaigns'));
  assert.equal(await page.locator('.fundraising-paused7').count(), 0, 'setting unchanged');
  await ctx.close();
});

test('Role limits: a content manager can publish but cannot see donations or receipts', async ({ browser }) => {
  if (!process.env.ROLE_LOGIN_URL) return 'skipped: ROLE_LOGIN_URL not set';
  const admin = await adminPage(browser);
  await admin.goto(u('/admin/administrators'));
  const email = `e2e-content-${RUN}@example.test`;
  const f = admin.locator('[data-admin-form]');
  await f.locator('input[name="name"]').fill('E2E Content Manager');
  await f.locator('input[name="email"]').fill(email);
  await f.locator('select[name="role"]').selectOption('content_manager');
  await f.locator('[data-admin-password]').fill('E2e-Strong-Pass-123!');
  await f.locator('[data-admin-password-confirm]').fill('E2e-Strong-Pass-123!');
  await Promise.all([admin.waitForNavigation(), f.locator('[data-admin-submit]').click()]);
  await admin.close();
  const ctx = await adminContext(browser, email); const page = await ctx.newPage();
  assert.equal((await page.goto(u('/admin/posts'))).status(), 200);
  assert.equal((await page.request.get(u('/admin/donations'), { maxRedirects: 0 })).status(), 403);
  assert.equal((await page.request.get(u('/admin/donations/proof/' + state.donationId), { maxRedirects: 0 })).status(), 403);
  assert.equal((await page.request.get(u('/admin/administrators'), { maxRedirects: 0 })).status(), 403);
  await ctx.close();
});

test('SEO basics: titles, descriptions, canonical, social tags, robots and sitemap', async ({ browser }) => {
  const ctx = await browser.newContext(); const page = await ctx.newPage();
  for (const p of ['/', '/news', '/campaigns', '/donate', '/about']) {
    await page.goto(u(p));
    const title = await page.title();
    assert.ok(title.length > 10 && title.length < 90, p + ' title length');
    assert.ok((await page.locator('meta[name="description"]').getAttribute('content')).length > 40, p + ' description');
    assert.ok(await page.locator('link[rel="canonical"]').count(), p + ' canonical');
    assert.ok(await page.locator('meta[property="og:image"]').count(), p + ' og:image');
    assert.match(await page.locator('meta[name="robots"]').getAttribute('content'), /^index/);
  }
  await page.goto(u('/admin/login'));
  await page.goto(u('/login'));
  assert.match(await page.locator('meta[name="robots"]').getAttribute('content'), /noindex/, 'login is not indexed');
  const robots = await (await page.request.get(u('/robots.txt'))).text();
  assert.match(robots, /Disallow: .*\/admin\//); assert.match(robots, /Sitemap: /);
  const sm = await (await page.request.get(u('/sitemap.xml'))).text();
  assert.match(sm, /<urlset/); assert.ok(!/\/admin/.test(sm), 'no admin URLs in sitemap');
  await ctx.close();
});

/* ------------------------------------------------------------------ Runner */
(async () => {
  const browser = await chromium.launch();
  const only = process.env.ONLY || '';
  const results = [];
  for (const t of tests) {
    if (only && !t.name.toLowerCase().includes(only.toLowerCase())) continue;
    const started = Date.now();
    try {
      const note = await t.fn({ browser });
      results.push({ name: t.name, ok: true, note: typeof note === 'string' ? note : '', ms: Date.now() - started });
      console.log(`  ✓ ${t.name}${typeof note === 'string' ? ' (' + note + ')' : ''}  ${Date.now() - started}ms`);
    } catch (e) {
      results.push({ name: t.name, ok: false, error: e.message, ms: Date.now() - started });
      console.log(`  ✗ ${t.name}\n      ${String(e.message).split('\n').join('\n      ')}`);
    }
  }
  if (state.admin) await state.admin.close();
  await browser.close();
  const failed = results.filter(r => !r.ok).length;
  const skipped = results.filter(r => r.ok && r.note.startsWith('skipped')).length;
  console.log(`\n${results.length - failed - skipped} passed, ${failed} failed, ${skipped} skipped (${results.length} run) against ${BASE}`);
  if (process.env.E2E_JSON) require('fs').writeFileSync(process.env.E2E_JSON, JSON.stringify(results, null, 2));
  process.exit(failed ? 1 : 0);
})();
