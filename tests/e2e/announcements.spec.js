
const { test, expect } = require('@playwright/test');
const { login, unique } = require('./helpers');

test.describe.configure({ mode: 'serial' });

test.describe('the public calendar', () => {
  test('renders a FullCalendar month grid', async ({ page }) => {
    await page.goto('announcements/calendar.php');

    const calendar = page.locator('#calendar');
    await expect(calendar).toBeVisible();

    await expect(calendar.locator('.fc-toolbar')).toBeVisible();
    await expect(calendar.locator('.fc-daygrid')).toBeVisible();
  });

  test('the toolbar buttons render with their icons', async ({ page }) => {
    await page.goto('announcements/calendar.php');

    const prev = page.locator('#calendar .fc-prev-button');
    await expect(prev).toBeVisible();

    const box = await prev.boundingBox();
    expect(box.width).toBeGreaterThan(10);
    expect(box.height).toBeGreaterThan(10);
  });

  test('can page to the next month without error', async ({ page }) => {
    await page.goto('announcements/calendar.php');

    const title = page.locator('#calendar .fc-toolbar-title');
    const before = await title.innerText();

    await page.locator('#calendar .fc-next-button').click();
    await expect(title).not.toHaveText(before);
  });

  test('the feed only exposes published announcements', async ({ page }) => {
    const response = await page.request.get('announcements/feed.php');
    const events = await response.json();

    expect(events.length).toBeGreaterThan(0);

    for (const event of events) {
      expect(event.title).toBeTruthy();
      expect(event.start).toBeTruthy();

      expect(event.title).not.toMatch(/^\[draft\]/i);
    }
  });

  test('an announcement detail page shows its content', async ({ page }) => {
    await page.goto('announcements/view.php?id=1');
    await expect(page.locator('h1, h2').first()).toBeVisible();
    await expect(page.locator('body')).not.toContainText(/Fatal error/);
  });

  test('residents cannot post or comment', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('announcements/calendar.php');

    await expect(page.locator('form[action*="announcement_save"]')).toHaveCount(0);
    await expect(page.locator('textarea[name="comment"]')).toHaveCount(0);

    const response = await page.goto('admin/announcements/edit.php');
    expect(response.status()).toBe(403);
  });
});

test.describe('publishing an announcement', () => {
  let title;

  test('an official can publish to the shared calendar', async ({ page }) => {
    title = unique('Anti-rabies vaccination drive');

    await login(page, 'official');
    await page.goto('admin/announcements/edit.php');

    await page.fill('input[name="title"]', title);
    await page.fill('textarea[name="body"]', 'Free anti-rabies vaccination for dogs and cats at the barangay hall.');
    await page.selectOption('select[name="category"]', 'vaccination_drive');
    await page.fill('input[name="location_text"]', 'Barangay Hall, San Isidro');

    const start = new Date(Date.now() + 24 * 60 * 60 * 1000);
    const iso = `${start.toISOString().slice(0, 10)}T09:00`;
    await page.fill('input[name="event_start"]', iso);

    await page.selectOption('select[name="status"]', 'published');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('body')).toContainText(title);
  });

  test('the new announcement appears in the public feed', async ({ page }) => {
    const response = await page.request.get('announcements/feed.php');
    const events = await response.json();

    expect(events.some((e) => e.title === title)).toBe(true);
  });

  test('a resident can read it', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('announcements/calendar.php');
    await expect(page.locator('body')).toContainText(title);
  });

  test('a Facebook link is accepted and rendered as an outbound link', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/announcements/edit.php');

    const fbTitle = unique('Adoption day');
    await page.fill('input[name="title"]', fbTitle);
    await page.fill('textarea[name="body"]', 'Meet adoptable pets at the covered court this weekend.');
    await page.selectOption('select[name="category"]', 'adoption_event');
    await page.fill('input[name="facebook_url"]', 'https://www.facebook.com/examplebarangay/posts/12345');
    await page.selectOption('select[name="status"]', 'published');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('announcements/calendar.php');
    await page.locator(`a:has-text("${fbTitle}")`).first().click();

    const outbound = page.locator('a[href*="facebook.com"]').first();
    await expect(outbound).toBeVisible();
    await expect(outbound).toHaveAttribute('rel', /noopener/);
    await expect(outbound).toHaveAttribute('target', '_blank');
  });

  test('a draft is not published to the feed', async ({ page }) => {
    const draftTitle = unique('Draft TNR schedule');

    await login(page, 'official');
    await page.goto('admin/announcements/edit.php');
    await page.fill('input[name="title"]', draftTitle);
    await page.fill('textarea[name="body"]', 'Not ready to publish yet.');
    await page.selectOption('select[name="status"]', 'draft');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();

    const response = await page.request.get('announcements/feed.php');
    const events = await response.json();
    expect(events.some((e) => e.title === draftTitle)).toBe(false);

    await page.goto('announcements/calendar.php');
    await expect(page.locator('body')).not.toContainText(draftTitle);
  });
});

test.describe('educational resources', () => {
  test('the library lists published articles by category', async ({ page }) => {
    await page.goto('resources/index.php');

    const links = page.locator('a[href*="/resources/view.php?id="]');
    expect(await links.count()).toBeGreaterThan(0);

    await expect(page.locator('body')).toContainText(/rabies/i);
  });

  test('an article renders its body', async ({ page }) => {
    await page.goto('resources/index.php');
    await page.locator('a[href*="/resources/view.php?id="]').first().click();

    await expect(page.locator('h1, h2').first()).toBeVisible();
    const text = await page.locator('main').innerText();
    expect(text.length).toBeGreaterThan(200);
  });

  test('a resident cannot edit the library', async ({ page }) => {
    await login(page, 'resident');
    const response = await page.goto('admin/resources/edit.php');
    expect(response.status()).toBe(403);
  });
});

test.describe('analytics', () => {
  test('the dashboard reports volume, response time and outcomes', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/analytics/index.php');

    const body = await page.locator('main').innerText();
    expect(body).toMatch(/report/i);
    expect(body).toMatch(/status|outcome/i);

    await expect(page.locator('canvas').first()).toBeVisible();
    const rendered = await page.evaluate(() => {
      const c = document.querySelector('canvas');
      return c ? c.width > 0 && c.height > 0 : false;
    });
    expect(rendered).toBe(true);
  });

  test('the monthly summary can be exported as CSV', async ({ page }) => {
    await login(page, 'official');

    const month = new Date().toISOString().slice(0, 7);
    const response = await page.request.get(
      `admin/analytics/monthly_summary.php?month=${month}&export=csv`
    );

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toMatch(/csv|octet-stream/);

    const text = await response.text();
    expect(text.split('\n').length).toBeGreaterThan(1);
  });

  test('a resident cannot see analytics', async ({ page }) => {
    await login(page, 'resident');
    const response = await page.goto('admin/analytics/index.php');
    expect(response.status()).toBe(403);
  });
});
