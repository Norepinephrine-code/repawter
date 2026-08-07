
const { test, expect } = require('@playwright/test');
const { login, logout, unique, USERS, PASSWORD } = require('./helpers');

test.describe('registration', () => {
  test('a new resident can register and is signed in', async ({ page }) => {
    const email = `${unique('resident')}@example.test`;

    await page.goto('auth/register.php');
    await page.fill('input[name="full_name"]', 'Test Resident');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.fill('input[name="password_confirm"]', PASSWORD);
    await page.selectOption('select[name="barangay_id"]', { index: 1 });
    await page.click('main button[type="submit"]');

    await expect(page.locator('#profileDropdown')).toContainText('Test Resident');
  });

  test('a duplicate email is rejected', async ({ page }) => {
    await page.goto('auth/register.php');
    await page.fill('input[name="full_name"]', 'Impostor');
    await page.fill('input[name="email"]', USERS.resident.email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.fill('input[name="password_confirm"]', PASSWORD);
    await page.click('main button[type="submit"]');

    await expect(page.locator('body')).toContainText(/already registered|already in use/i);
    await expect(page.locator('#profileDropdown')).toHaveCount(0);
  });

  test('a short password is rejected', async ({ page }) => {
    await page.goto('auth/register.php');
    await page.fill('input[name="full_name"]', 'Test Resident');
    await page.fill('input[name="email"]', `${unique('short')}@example.test`);
    await page.fill('input[name="password"]', 'abc');
    await page.fill('input[name="password_confirm"]', 'abc');
    await page.click('main button[type="submit"]');

    await expect(page.locator('body')).toContainText(/at least 8 characters/i);
    await expect(page.locator('#profileDropdown')).toHaveCount(0);
  });

  test('mismatched password confirmation is rejected', async ({ page }) => {
    await page.goto('auth/register.php');
    await page.fill('input[name="full_name"]', 'Test Resident');
    await page.fill('input[name="email"]', `${unique('mismatch')}@example.test`);
    await page.fill('input[name="password"]', PASSWORD);
    await page.fill('input[name="password_confirm"]', 'SomethingElse123!');
    await page.click('main button[type="submit"]');

    await expect(page.locator('#profileDropdown')).toHaveCount(0);
  });

  test('registration always creates a resident, never staff', async ({ page }) => {

    await page.goto('auth/register.php');
    await expect(page.locator('[name="role"]')).toHaveCount(0);
  });
});

test.describe('login', () => {
  test('valid credentials sign a resident in and land on the home page', async ({ page }) => {
    await login(page, 'resident');
    await expect(page.locator('#profileDropdown')).toContainText(USERS.resident.name);
  });

  for (const role of ['admin', 'official', 'welfare']) {
    test(`a ${role} lands on the staff dashboard`, async ({ page }) => {
      await login(page, role);
      await expect(page).toHaveURL(/admin\/dashboard\.php/);
      await expect(page.locator('h1')).toContainText(/dashboard/i);
    });
  }

  test('a wrong password is refused without revealing which field was wrong', async ({ page }) => {
    await page.goto('auth/login.php');
    await page.fill('input[name="email"]', USERS.resident.email);
    await page.fill('input[name="password"]', 'WrongPassword123!');
    await page.click('main button[type="submit"]');

    await expect(page).toHaveURL(/auth\/login\.php/);
    await expect(page.locator('.alert-danger')).toContainText(/invalid credentials/i);
    await expect(page.locator('#profileDropdown')).toHaveCount(0);
  });

  test('an unknown email is refused with the same message', async ({ page }) => {
    await page.goto('auth/login.php');
    await page.fill('input[name="email"]', 'nobody@example.test');
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-danger')).toContainText(/invalid credentials/i);
  });

  test('the email field is preserved after a failed attempt', async ({ page }) => {
    await page.goto('auth/login.php');
    await page.fill('input[name="email"]', USERS.resident.email);
    await page.fill('input[name="password"]', 'nope');
    await page.click('main button[type="submit"]');

    await expect(page.locator('input[name="email"]')).toHaveValue(USERS.resident.email);
  });
});

test.describe('sessions', () => {
  test('logging out ends the session and protected pages become unreachable', async ({ page }) => {
    await login(page, 'resident');
    await logout(page);

    await page.goto('reports/my_reports.php');
    await expect(page).toHaveURL(/auth\/login\.php/);
  });

  test('a guest is sent to login and back to where they were going', async ({ page }) => {
    await page.goto('reports/submit.php');
    await expect(page).toHaveURL(/auth\/login\.php/);

    await page.fill('input[name="email"]', USERS.resident.email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('main button[type="submit"]');

    await expect(page).toHaveURL(/reports\/submit\.php/);
  });

  test('the session cookie is HttpOnly and SameSite=Lax', async ({ page, context }) => {
    await login(page, 'resident');

    const cookies = await context.cookies();
    const session = cookies.find((c) => /sess/i.test(c.name));

    expect(session, 'no session cookie was set').toBeTruthy();
    expect(session.httpOnly, 'session cookie must be HttpOnly').toBe(true);
    expect(session.sameSite).toBe('Lax');

  });
});

test.describe('account status', () => {
  test('a suspended account cannot log in', async ({ page }) => {

    await login(page, 'admin');

    await page.goto('admin/users/view.php?id=7');
    await expect(page.locator('body')).toContainText(USERS.resident2.name);

    const suspendForm = page.locator('form:has(button:has-text("Suspend"))').first();
    await suspendForm.locator('input[name="reason"]').fill('E2E suspension check');

    page.once('dialog', (d) => d.accept());
    await suspendForm.locator('button:has-text("Suspend")').click();
    await expect(page.locator('body')).toContainText(/suspended/i);

    await logout(page);

    await page.goto('auth/login.php');
    await page.fill('input[name="email"]', USERS.resident2.email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('#profileDropdown')).toHaveCount(0);

    await login(page, 'admin');
    await page.goto('admin/users/view.php?id=7');

    const reactivate = page.locator('form:has(input[name="status"][value="active"])');
    await reactivate.locator('button[type="submit"]').click();
    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('a flagged account can still log in', async ({ page }) => {

    await login(page, 'flagged');
    await expect(page.locator('#profileDropdown')).toContainText(USERS.flagged.name);
  });
});

test.describe('profile', () => {
  test('a resident can update their contact details', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('auth/profile.php');

    const number = `0917${Math.floor(1000000 + Math.random() * 8999999)}`;
    await page.fill('input[name="contact_number"]', number);
    await page.click('form:has(input[name="contact_number"]) button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();
    await page.goto('auth/profile.php');
    await expect(page.locator('input[name="contact_number"]')).toHaveValue(number);
  });

  test('changing a password requires the current one', async ({ page }) => {
    await login(page, 'resident');
    await page.goto('auth/profile.php');

    const form = page.locator('form:has(input[name="new_password"])');
    await form.locator('input[name="current_password"]').fill('DefinitelyWrong1!');
    await form.locator('input[name="new_password"]').fill('BrandNewPass123!');
    await form.locator('input[name="new_password_confirm"]').fill('BrandNewPass123!');
    await form.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-danger')).toBeVisible();

    await logout(page);
    await login(page, 'resident');
  });
});
