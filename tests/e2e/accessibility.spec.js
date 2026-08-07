
const { test, expect } = require('@playwright/test');
const { login, USERS, PASSWORD } = require('./helpers');

const PAGES = [
  'auth/login.php',
  'auth/register.php',
  'adoption/gallery.php',
  'resources/index.php',
  'announcements/calendar.php',
];

test.describe('document structure', () => {
  for (const path of PAGES) {
    test(`${path} has the expected landmarks`, async ({ page }) => {
      await page.goto(path);

      await expect(page.locator('html')).toHaveAttribute('lang', 'en');
      await expect(page.locator('nav[aria-label="Main navigation"]')).toHaveCount(1);
      await expect(page.locator('main#main-content')).toHaveCount(1);
      await expect(page.locator('footer')).toHaveCount(1);

      const title = await page.title();
      expect(title.length).toBeGreaterThan(('RePawter').length);
    });
  }

  test('every page declares a meta description', async ({ page }) => {
    await page.goto('./');
    const description = await page.locator('meta[name="description"]').getAttribute('content');
    expect(description).toBeTruthy();
    expect(description.length).toBeGreaterThan(40);
  });

  test('headings start at a single h1 per page', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/reports/index.php');
    expect(await page.locator('h1').count()).toBeLessThanOrEqual(1);
  });
});

test.describe('keyboard access', () => {
  test('the skip link is the first stop and jumps to the content', async ({ page }) => {
    await page.goto('./');

    await page.keyboard.press('Tab');

    const skip = page.locator('.paw-skip-link');
    await expect(skip).toBeFocused();

    await expect(skip).toBeVisible();

    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#main-content$/);
  });

  test('a form can be completed and submitted with the keyboard alone', async ({ page }) => {
    await page.goto('auth/login.php');

    await page.locator('input[name="email"]').focus();
    await page.keyboard.type(USERS.resident.email);
    await page.keyboard.press('Tab');
    await page.keyboard.type(PASSWORD);
    await page.keyboard.press('Enter');

    await expect(page.locator('#profileDropdown')).toBeVisible();
  });

  test('focused controls show a visible focus ring', async ({ page }) => {
    await page.goto('auth/login.php');

    const email = page.locator('input[name="email"]');
    await email.focus();

    const shadow = await email.evaluate((el) => getComputedStyle(el).boxShadow);
    expect(shadow, 'focused input has no focus ring').not.toBe('none');
  });
});

test.describe('forms', () => {
  test('every input on the auth forms has a label', async ({ page }) => {
    for (const path of ['auth/login.php', 'auth/register.php']) {
      await page.goto(path);

      const unlabelled = await page.evaluate(() => {
        const fields = Array.from(
          document.querySelectorAll('input:not([type=hidden]):not([type=submit]), select, textarea')
        );
        return fields
          .filter((field) => {
            if (field.getAttribute('aria-label')) return false;
            if (field.getAttribute('aria-labelledby')) return false;
            if (field.closest('label')) return false;
            return !(field.id && document.querySelector(`label[for="${field.id}"]`));
          })
          .map((field) => field.name || field.id || field.outerHTML.slice(0, 60));
      });

      expect(unlabelled, `unlabelled fields on ${path}`).toEqual([]);
    }
  });

  test('required fields are marked for assistive technology', async ({ page }) => {
    await page.goto('auth/register.php');

    for (const name of ['full_name', 'email', 'password']) {
      const field = page.locator(`[name="${name}"]`);
      await expect(field).toHaveAttribute('required', '');
    }
  });
});

test.describe('images', () => {
  test('every image carries alt text', async ({ page }) => {
    for (const path of ['adoption/gallery.php', 'resources/index.php']) {
      await page.goto(path);

      const missing = await page.evaluate(() =>
        Array.from(document.images)
          .filter((img) => !img.hasAttribute('alt'))
          .map((img) => img.src)
      );

      expect(missing, `images with no alt attribute on ${path}`).toEqual([]);
    }
  });
});

test.describe('status messaging', () => {
  test('flash messages are announced to screen readers', async ({ page }) => {
    await page.goto('auth/login.php');
    await page.fill('input[name="email"]', 'nobody@example.test');
    await page.fill('input[name="password"]', 'wrong-password');
    await page.click('main button[type="submit"]');

    const region = page.locator('.paw-flash-region');
    await expect(region).toHaveAttribute('aria-live', 'polite');
    await expect(region.locator('[role="alert"]').first()).toBeVisible();
  });

  test('status is not conveyed by colour alone', async ({ page }) => {
    await login(page, 'official');
    await page.goto('admin/reports/index.php');

    const badge = page.locator('[class*="badge-status--"]').first();
    await expect(badge).not.toBeEmpty();
  });
});

test.describe('reduced motion', () => {
  test('animations are suppressed when the user asks for less motion', async ({ page }) => {
    await page.goto('adoption/gallery.php');

    const read = () => page.evaluate(() => {
      const card = document.querySelector('.pet-card');
      return {
        prefersReduce: matchMedia('(prefers-reduced-motion: reduce)').matches,
        duration: card ? getComputedStyle(card).transitionDuration : null,
      };
    });

    const normal = await read();
    expect(normal.duration, 'no .pet-card on the gallery to measure').not.toBeNull();
    expect(normal.prefersReduce).toBe(false);
    expect(parseFloat(normal.duration)).toBeGreaterThan(0.05);

    await page.emulateMedia({ reducedMotion: 'reduce' });

    const reduced = await read();
    expect(reduced.prefersReduce, 'reduced-motion emulation did not apply').toBe(true);
    expect(parseFloat(reduced.duration)).toBeLessThan(0.05);
  });
});
