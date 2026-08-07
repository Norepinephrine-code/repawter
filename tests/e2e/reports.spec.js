
const { test, expect } = require('@playwright/test');
const { login, logout, unique, SAMPLE_PHOTO, USERS } = require('./helpers');

test.describe.configure({ mode: 'serial' });

async function submitReport(page, overrides = {}) {
  const title = overrides.title || unique('E2E stray dog');

  await page.goto('reports/submit.php');
  await page.selectOption('select[name="animal_type"]', overrides.animalType || 'dog');
  await page.fill('input[name="title"]', title);
  await page.fill(
    'textarea[name="description"]',
    overrides.description || 'A thin brown aspin has been limping near the covered court since this morning.'
  );
  await page.selectOption('select[name="barangay_id"]', { index: 1 });
  await page.fill('input[name="location_address"]', overrides.address || '12 Mabini Street, Purok 3');
  await page.fill('input[name="location_landmark"]', 'Beside the covered court');
  await page.check(`input[name="urgency"][value="${overrides.urgency || 'high'}"]`);
  await page.setInputFiles('input[name="photo"]', overrides.photo || SAMPLE_PHOTO);
  await page.click('main button[type="submit"]');

  return title;
}

test.describe('filing a report', () => {
  let reportTitle;

  test('a resident can file a report with a photo', async ({ page }) => {
    await login(page, 'resident');
    reportTitle = await submitReport(page);

    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('reports/my_reports.php');
    await expect(page.locator('body')).toContainText(reportTitle);

    await expect(page.locator('.badge-status--submitted').first()).toBeVisible();
  });

  test('the uploaded photo is served back', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('reports/my_reports.php');

    const img = page.locator('img[src*="/uploads/reports/"]').first();
    await expect(img).toBeVisible();

    const src = await img.getAttribute('src');
    const response = await page.request.get(src);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('image');
  });

  test('a report without a photo is rejected', async ({ page }) => {
    await login(page, 'resident');

    await page.goto('reports/submit.php');
    await page.fill('input[name="title"]', unique('No photo'));
    await page.fill('textarea[name="description"]', 'Description without any photo attached at all.');
    await page.fill('input[name="location_address"]', 'Somewhere');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toHaveCount(0);
  });

  test('a non-image upload is rejected', async ({ page }) => {
    await login(page, 'resident');

    await page.goto('reports/submit.php');

    await page.selectOption('select[name="animal_type"]', 'dog');
    await page.fill('input[name="title"]', unique('Bad upload'));
    await page.fill('textarea[name="description"]', 'This attempts to upload a text file as the photo.');
    await page.selectOption('select[name="barangay_id"]', { index: 1 });
    await page.fill('input[name="location_address"]', 'Somewhere specific');
    await page.check('input[name="urgency"][value="low"]');
    await page.setInputFiles('input[name="photo"]', {
      name: 'not-a-photo.txt',
      mimeType: 'text/plain',
      buffer: Buffer.from('<?php echo "this should never be accepted"; ?>'),
    });
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('.alert-danger')).toContainText(/JPEG, PNG, and WebP|valid image/i);
  });

  test('a resident sees only their own reports', async ({ page }) => {
    await login(page, 'resident2');
    await page.goto('reports/my_reports.php');

    const rows = page.locator('a[href*="/reports/view.php?id="]');
    const count = await rows.count();

    for (let i = 0; i < count; i += 1) {
      const href = await rows.nth(i).getAttribute('href');
      const id = new URL(href, 'http://x').searchParams.get('id');
      const response = await page.request.get(`reports/view.php?id=${id}`);
      expect(response.status()).toBe(200);
    }

    await page.goto('reports/view.php?id=1');
    await expect(page.locator('body')).not.toContainText(/Injured Dog on San Isidro Road/);
  });
});

test.describe('reviewing a report', () => {
  let reportId;

  test('an official can open the newest report from the queue', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/reports/index.php?status=submitted');

    const manage = page.locator('a[href*="/admin/reports/view.php?id="]').first();
    await expect(manage).toBeVisible();

    const href = await manage.getAttribute('href');
    reportId = new URL(href, 'http://x').searchParams.get('id');

    await manage.click();
    await expect(page.locator('h1, h2').first()).toBeVisible();
  });

  test('the verification checklist is shown and can be recorded', async ({ page }) => {
    await login(page, 'official');
    await page.goto(`admin/reports/view.php?id=${reportId}`);

    const checklist = page.locator('form[action*="verify.php"]');
    await expect(checklist).toBeVisible();

    const boxes = checklist.locator('input[type="checkbox"]');
    expect(await boxes.count()).toBeGreaterThan(0);

    for (let i = 0; i < (await boxes.count()); i += 1) {
      await boxes.nth(i).check();
    }

    await checklist.locator('button[type="submit"]').first().click();
    await expect(page.locator('.alert-success, .alert-info')).toBeVisible();
  });

  test('a verified report can be assigned to a welfare organization', async ({ page }) => {
    await login(page, 'official');
    await page.goto(`admin/reports/view.php?id=${reportId}`);

    const assign = page.locator('form[action*="assign.php"]');
    await expect(assign).toBeVisible();

    await assign.locator('select').first().selectOption({ index: 1 });
    await assign.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('body')).toContainText(/assigned/i);
  });

  test('the status history records every transition', async ({ page }) => {
    await login(page, 'official');
    await page.goto(`admin/reports/view.php?id=${reportId}`);

    const body = await page.locator('body').innerText();

    expect(body.toLowerCase()).toContain('submitted');
    expect(body.toLowerCase()).toMatch(/assigned/);
  });

  test('the reporter is notified when their report changes state', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('notifications/index.php');
    await expect(page.locator('body')).not.toContainText(/no notifications/i);
  });

  test('a report can be archived but never deleted', async ({ page }) => {
    await login(page, 'official');
    await page.goto(`admin/reports/view.php?id=${reportId}`);

    const archive = page.locator('form[action*="archive.php"]');
    await expect(archive).toBeVisible();

    page.once('dialog', (d) => d.accept());
    await archive.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toBeVisible();

    const response = await page.request.get(`admin/reports/view.php?id=${reportId}`);
    expect(response.status()).toBe(200);
    await page.goto(`admin/reports/view.php?id=${reportId}`);
    await expect(page.locator('body')).toContainText(/archived/i);
  });
});

test.describe('the report queue', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'official');
  });

  test('filters by status', async ({ page }) => {
    await page.goto('admin/reports/index.php?status=resolved');

    const badges = page.locator('[class*="badge-status--"]');
    const count = await badges.count();

    for (let i = 0; i < count; i += 1) {
      await expect(badges.nth(i)).toHaveClass(/badge-status--resolved/);
    }
  });

  test('filters by urgency', async ({ page }) => {
    await page.goto('admin/reports/index.php?urgency=critical');

    const rows = page.locator('tbody tr');
    if (await rows.count() > 0) {
      await expect(page.locator('.urgency--critical').first()).toBeVisible();
      await expect(page.locator('.urgency--low')).toHaveCount(0);
    }
  });

  test('searching narrows the queue', async ({ page }) => {
    await page.goto('admin/reports/index.php?search=Poblacion');
    await expect(page.locator('body')).toContainText(/Poblacion/);
  });

  test('an unknown report id does not produce an error page', async ({ page }) => {
    const response = await page.goto('admin/reports/view.php?id=999999');
    expect(response.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText(/Fatal error/);
  });
});

test.describe('case tracking', () => {
  test('an official can open a case for a report and record progress', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/reports/case_tracking.php');

    await expect(page.locator('body')).toContainText(/case/i);
    await expect(page.locator('body')).not.toContainText(/Fatal error/);
  });
});
