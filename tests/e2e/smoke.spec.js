
const { test, expect } = require('@playwright/test');
const {
  login,
  expectNoHorizontalOverflow,
  collectPageProblems,
  expectNoPhpErrors,
} = require('./helpers');

const PUBLIC_ROUTES = [
  ['home', './'],
  ['login', 'auth/login.php'],
  ['register', 'auth/register.php'],
  ['adoption gallery', 'adoption/gallery.php'],
  ['adoption index redirect', 'adoption/'],
  ['announcements calendar', 'announcements/calendar.php'],
  ['announcements index redirect', 'announcements/'],
  ['announcement detail', 'announcements/view.php?id=1'],
  ['resources index', 'resources/index.php'],
  ['resource detail', 'resources/view.php?id=1'],
  ['pet detail', 'adoption/pet.php?id=1'],
];

const RESIDENT_ROUTES = [
  ['submit a report', 'reports/submit.php'],
  ['my reports', 'reports/my_reports.php'],
  ['my reports redirect', 'reports/'],
  ['report detail', 'reports/view.php?id=1'],
  ['foster application form', 'foster/apply.php'],
  ['my foster applications', 'foster/my_applications.php'],
  ['foster index redirect', 'foster/'],
  ['notifications', 'notifications/index.php'],
  ['profile', 'auth/profile.php'],
  ['profile redirect', 'profile/'],
];

const STAFF_ROUTES = [
  ['dashboard', 'admin/dashboard.php'],
  ['admin index redirect', 'admin/'],
  ['report queue', 'admin/reports/index.php'],
  ['report review', 'admin/reports/view.php?id=2'],
  ['case tracking', 'admin/reports/case_tracking.php'],
  ['foster review queue', 'admin/foster/index.php'],
  ['foster application detail', 'admin/foster/view.php?id=1'],
  ['announcement admin', 'admin/announcements/index.php'],
  ['new announcement', 'admin/announcements/edit.php'],
  ['edit announcement', 'admin/announcements/edit.php?id=1'],
  ['resources admin', 'admin/resources/index.php'],
  ['new resource', 'admin/resources/edit.php'],
  ['analytics', 'admin/analytics/index.php'],
  ['monthly summary', 'admin/analytics/monthly_summary.php'],
];

const ADMIN_ONLY_ROUTES = [
  ['pets admin', 'admin/pets/index.php'],
  ['new pet', 'admin/pets/edit.php'],
  ['edit pet', 'admin/pets/edit.php?id=1'],
  ['adoption queue', 'admin/adoption/index.php'],
  ['adoption detail', 'admin/adoption/view.php?id=1'],
  ['users admin', 'admin/users/index.php'],
  ['user detail', 'admin/users/view.php?id=6'],
  ['verification criteria', 'admin/system/criteria.php'],
  ['audit logs', 'admin/system/audit_logs.php'],
];

async function expectPageHealthy(page, url) {
  const problems = collectPageProblems(page);

  const response = await page.goto(url);
  expect(response, `no response for ${url}`).not.toBeNull();
  expect(response.status(), `unexpected status for ${url}`).toBeLessThan(400);

  await expectNoPhpErrors(page);
  await expectNoHorizontalOverflow(page);

  await expect(page).toHaveTitle(/RePawter/);
  await expect(page.locator('main#main-content')).toHaveCount(1);

  expect(problems(), `page problems on ${url}`).toEqual([]);
}

test.describe('public pages', () => {
  for (const [name, url] of PUBLIC_ROUTES) {
    test(`${name} renders for a guest`, async ({ page }) => {
      await expectPageHealthy(page, url);
    });
  }

  test('the announcements feed returns valid JSON', async ({ page }) => {
    const response = await page.request.get('announcements/feed.php');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');

    const events = await response.json();
    expect(Array.isArray(events)).toBe(true);

    for (const event of events) {
      expect(event).toHaveProperty('title');
      expect(event).toHaveProperty('start');
    }
  });

  test('the 404 page answers with a 404 status', async ({ page }) => {
    const response = await page.goto('no/such/page.php');
    expect(response.status()).toBe(404);
  });
});

test.describe('resident pages', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'resident');
  });

  for (const [name, url] of RESIDENT_ROUTES) {
    test(`${name} renders`, async ({ page }) => {
      await expectPageHealthy(page, url);
    });
  }
});

test.describe('staff pages', () => {
  for (const role of ['admin', 'official', 'welfare']) {
    test.describe(`as ${role}`, () => {
      test.beforeEach(async ({ page }) => {
        await login(page, role);
      });

      for (const [name, url] of STAFF_ROUTES) {
        test(`${name} renders`, async ({ page }) => {
          await expectPageHealthy(page, url);
        });
      }
    });
  }
});

test.describe('admin-only pages', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'admin');
  });

  for (const [name, url] of ADMIN_ONLY_ROUTES) {
    test(`${name} renders`, async ({ page }) => {
      await expectPageHealthy(page, url);
    });
  }
});

test.describe('responsive layout @mobile', () => {
  const MOBILE_ROUTES = ['/', 'adoption/gallery.php', '/resources/index.php', '/auth/login.php'];

  for (const url of MOBILE_ROUTES) {
    test(`${url} fits the viewport`, async ({ page }) => {
      await page.goto(url);
      await expectNoHorizontalOverflow(page);
    });
  }

  test('the navigation collapses behind a toggle', async ({ page }) => {
    await page.goto('./');

    const toggle = page.locator('.navbar-toggler');
    await expect(toggle).toBeVisible();

    const menu = page.locator('#mainNav');
    await expect(menu).not.toBeVisible();

    await toggle.click();
    await expect(menu).toBeVisible();
    await expect(page.locator('#mainNav a[href$="/adoption/"]')).toBeVisible();
  });
});
