
const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

const MATRIX = [

  ['admin/dashboard.php',                     true,  true,  true],
  ['admin/reports/index.php',                 true,  true,  true],
  ['admin/reports/view.php?id=2',             true,  true,  true],
  ['admin/reports/case_tracking.php',         true,  true,  true],
  ['admin/foster/index.php',                  true,  true,  true],
  ['admin/announcements/index.php',           true,  true,  true],
  ['admin/resources/index.php',               true,  true,  true],
  ['admin/analytics/index.php',               true,  true,  true],

  ['admin/pets/index.php',                    true,  false, true],
  ['admin/pets/edit.php',                     true,  false, true],
  ['admin/adoption/index.php',                true,  false, true],

  ['admin/users/index.php',                   true,  true,  false],
  ['admin/users/view.php?id=6',               true,  true,  false],

  ['admin/system/criteria.php',               true,  false, false],
  ['admin/system/audit_logs.php',             true,  false, false],
];

const ROLE_COLUMN = { admin: 1, official: 2, welfare: 3 };

for (const role of Object.keys(ROLE_COLUMN)) {
  test.describe(`as ${role}`, () => {
    test.beforeEach(async ({ page }) => {
      await login(page, role);
    });

    for (const row of MATRIX) {
      const path = row[0];
      const allowed = row[ROLE_COLUMN[role]];

      test(`${allowed ? 'may open' : 'is refused'} ${path}`, async ({ page }) => {
        const response = await page.goto(path);

        if (allowed) {
          expect(response.status(), `${role} should be allowed on ${path}`).toBeLessThan(400);
        } else {
          expect(response.status(), `${role} must be refused on ${path}`).toBe(403);

          await expect(page.locator('body')).toContainText(/access denied/i);
          await expect(page.locator('body')).not.toContainText(/Fatal error/);
        }
      });
    }
  });
}

test.describe('as a resident', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'resident');
  });

  for (const row of MATRIX) {
    test(`is refused ${row[0]}`, async ({ page }) => {
      const response = await page.goto(row[0]);
      expect(response.status(), `a resident must never reach ${row[0]}`).toBe(403);
    });
  }

  test('cannot reach staff pages by POSTing to an action handler', async ({ page }) => {

    const response = await page.request.post('actions/users/user_status.php', {
      form: { id: '6', status: 'suspended', reason: 'nope' },
      maxRedirects: 0,
    });

    expect([302, 303, 403]).toContain(response.status());

    await page.goto('./');
    await expect(page.locator('#profileDropdown')).toBeVisible();
  });
});

test.describe('as a guest', () => {
  const PROTECTED = [
    'admin/dashboard.php',
    'admin/reports/index.php',
    'admin/users/index.php',
    'reports/submit.php',
    'reports/my_reports.php',
    'foster/apply.php',
    'notifications/index.php',
    'auth/profile.php',
  ];

  for (const path of PROTECTED) {
    test(`is sent to login from ${path}`, async ({ page }) => {
      await page.goto(path);
      await expect(page).toHaveURL(/auth\/login\.php/);
    });
  }
});

test.describe('navigation reflects permissions', () => {
  test('an official is not offered pets or adoptions', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/dashboard.php');

    const nav = page.locator('.paw-admin-subnav');
    await expect(nav).not.toContainText('Pets');
    await expect(nav).not.toContainText('Adoptions');
    await expect(nav).toContainText('Users');
  });

  test('a welfare organization is not offered user management', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/dashboard.php');

    const nav = page.locator('.paw-admin-subnav');
    await expect(nav).not.toContainText('Users');
    await expect(nav).not.toContainText('Audit Logs');
    await expect(nav).toContainText('Pets');
  });

  test('only an admin is offered the system section', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('admin/dashboard.php');

    const nav = page.locator('.paw-admin-subnav');
    await expect(nav).toContainText('Verification Criteria');
    await expect(nav).toContainText('Audit Logs');
  });

  test('a resident sees no admin link at all', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('./');

    await expect(page.locator('#mainNav a[href*="/admin/"]')).toHaveCount(0);
  });
});
