
const { test, expect } = require('@playwright/test');
const { login, unique, USERS, PASSWORD } = require('./helpers');

test.describe('response headers', () => {
  test('the security headers are present on a page response', async ({ page }) => {
    const response = await page.goto('auth/login.php');
    const headers = response.headers();

    expect(headers['x-content-type-options']).toBe('nosniff');
    expect(headers['x-frame-options']).toBe('SAMEORIGIN');
    expect(headers['referrer-policy']).toBe('strict-origin-when-cross-origin');
    expect(headers['permissions-policy']).toContain('geolocation=()');
  });

  test('the content security policy locks the page down to this origin', async ({ page }) => {
    const response = await page.goto('./');
    const csp = response.headers()['content-security-policy'];

    expect(csp, 'no CSP header was sent').toBeTruthy();
    expect(csp).toContain("default-src 'self'");
    expect(csp).toContain("object-src 'none'");
    expect(csp).toContain("base-uri 'self'");
    expect(csp).toContain("form-action 'self'");

    expect(csp).not.toMatch(/script-src[^;]*https?:\/\//);
    expect(csp).not.toMatch(/style-src[^;]*https?:\/\//);
  });

  test('HSTS is not sent over plain HTTP', async ({ page }) => {

    const response = await page.goto('./');
    expect(response.headers()['strict-transport-security']).toBeUndefined();
  });
});

test.describe('CSRF protection', () => {
  test('every POST form carries a token', async ({ page }) => {
    await login(page, 'resident');

    for (const path of ['reports/submit.php', 'foster/apply.php', '/auth/profile.php']) {
      await page.goto(path);

      const forms = page.locator('form[method="post" i]');
      const count = await forms.count();
      expect(count, `no POST form found on ${path}`).toBeGreaterThan(0);

      for (let i = 0; i < count; i += 1) {
        await expect(
          forms.nth(i).locator('input[name="_csrf"]'),
          `form ${i} on ${path} has no CSRF token`
        ).toHaveCount(1);
      }
    }
  });

  test('a POST without a token is rejected', async ({ page }) => {
    await login(page, 'resident');

    const response = await page.request.post('actions/foster/foster_submit.php', {
      form: {
        preferred_animal_type: 'dog',
        household_capacity: '1',
        preferred_notes: unique('csrf-attempt'),
      },
      maxRedirects: 0,
    });

    expect(response.status()).toBe(302);

    await page.goto('foster/my_applications.php');
    await expect(page.locator('body')).not.toContainText('csrf-attempt');
  });

  test('a POST with a forged token is rejected', async ({ page }) => {
    await login(page, 'resident');

    const response = await page.request.post('actions/foster/foster_submit.php', {
      form: {
        _csrf: 'f'.repeat(64),
        preferred_animal_type: 'dog',
        household_capacity: '1',
      },
      maxRedirects: 0,
    });

    expect(response.status()).toBe(302);
  });

  test('action handlers refuse a GET request', async ({ page }) => {
    await login(page, 'admin');

    const handlers = [
      'actions/reports/report_submit.php',
      'actions/foster/foster_submit.php',
      'actions/users/user_status.php',
      'actions/announcements/announcement_save.php',
    ];

    for (const handler of handlers) {
      const response = await page.request.get(handler, { maxRedirects: 0 });
      expect([302, 303, 403, 405], `${handler} answered a GET with ${response.status()}`)
        .toContain(response.status());
    }
  });
});

test.describe('output escaping', () => {
  test('a script tag in user input is rendered as text, not executed', async ({ page }) => {
    const payload = `<script>window.__xss = true;</script>`;
    const marker = unique('xss');

    await login(page, 'resident');
    await page.goto('foster/apply.php');
    await page.fill('textarea[name="preferred_notes"]', `${marker} ${payload}`);
    await page.fill('input[name="household_capacity"]', '1');
    await page.click('main button[type="submit"]');

    await page.goto('foster/my_applications.php');

    expect(await page.evaluate(() => window.__xss)).toBeUndefined();
    await expect(page.locator('body')).toContainText(marker);
    expect(await page.locator('script:not([src])').count()).toBeLessThan(20);
  });

  test('a quote-breaking value in a search field does not break the markup', async ({ page }) => {
    await login(page, 'official');
    await page.goto(`admin/reports/index.php?search=${encodeURIComponent('" onmouseover="alert(1)')}`);

    expect(await page.locator('[onmouseover]').count()).toBe(0);
    await expect(page.locator('body')).not.toContainText(/Fatal error|SQLSTATE/);
  });
});

test.describe('SQL injection', () => {
  const PAYLOADS = ["' OR '1'='1", "1; DROP TABLE users; --", "1' UNION SELECT NULL--"];

  test('injection attempts in query parameters are handled safely', async ({ page }) => {
    await login(page, 'official');

    for (const payload of PAYLOADS) {
      const encoded = encodeURIComponent(payload);

      for (const url of [
        `admin/reports/index.php?search=${encoded}`,
        `admin/reports/view.php?id=${encoded}`,
        `adoption/pet.php?id=${encoded}`,
      ]) {
        const response = await page.goto(url);
        expect(response.status(), `${url} returned ${response.status()}`).toBeLessThan(500);
        await expect(page.locator('body')).not.toContainText(/SQLSTATE|SQL syntax/);
      }
    }

    await page.goto('admin/users/index.php');
    await expect(page.locator('body')).toContainText(USERS.resident.name);
  });
});

test.describe('uploads', () => {
  test('a PHP file disguised as a photo is not accepted', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('reports/submit.php');

    await page.selectOption('select[name="animal_type"]', 'dog');
    await page.fill('input[name="title"]', unique('Upload probe'));
    await page.fill('textarea[name="description"]', 'Attempting to upload a PHP payload as the report photo.');
    await page.selectOption('select[name="barangay_id"]', { index: 1 });
    await page.fill('input[name="location_address"]', 'Somewhere');
    await page.check('input[name="urgency"][value="low"]');
    await page.setInputFiles('input[name="photo"]', {
      name: 'shell.php.jpg',
      mimeType: 'image/jpeg',
      buffer: Buffer.from('<?php system($_GET["c"]); ?>'),
    });
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('.alert-success')).toHaveCount(0);
  });
});

test.describe('session fixation', () => {
  test('the session identifier changes when a user logs in', async ({ page, context }) => {
    await page.goto('auth/login.php');
    const before = (await context.cookies()).find((c) => /sess/i.test(c.name));

    await page.fill('input[name="email"]', USERS.resident.email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('main button[type="submit"]');
    await expect(page.locator('#profileDropdown')).toBeVisible();

    const after = (await context.cookies()).find((c) => /sess/i.test(c.name));

    expect(after).toBeTruthy();
    if (before) {
      expect(after.value, 'session id must be regenerated on login').not.toBe(before.value);
    }
  });
});
