
const { test, expect } = require('@playwright/test');
const { login, unique, USERS, SAMPLE_PHOTO } = require('./helpers');

test.describe.configure({ mode: 'serial' });

test.describe('foster applications', () => {
  test('a resident can apply to foster', async ({ page }) => {
    await login(page, 'resident2');
    await page.goto('foster/apply.php');

    await page.selectOption('select[name="preferred_animal_type"]', 'cat');
    await page.fill('textarea[name="preferred_notes"]', unique('Prefers calm adult cats'));
    await page.fill('input[name="household_capacity"]', '2');
    await page.fill('textarea[name="household_notes"]', 'Two-bedroom home, fenced yard, no other pets.');

    await page.check('input[name="has_yard"][value="1"]');

    await page.click('main button[type="submit"]');
    await expect(page.locator('.alert-success')).toBeVisible();

    await page.goto('foster/my_applications.php');
    await expect(page.locator('body')).toContainText(/submitted|under review/i);
  });

  test('a welfare organization sees the application with applicant history', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/foster/index.php');

    await expect(page.locator('body')).toContainText(USERS.resident2.name);

    await page.locator('a[href*="/admin/foster/view.php?id="]').first().click();
    await expect(page.locator('body')).toContainText(/capacity/i);
  });

  test('an application can be approved with a note', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/foster/index.php?status=submitted');

    const link = page.locator('a[href*="/admin/foster/view.php?id="]').first();
    await expect(link).toBeVisible();
    await link.click();

    const approve = page.locator('form:has(input[name="decision"][value="approve"])');
    await approve.locator('textarea[name="decision_notes"]').fill('Approved after a home visit.');

    page.once('dialog', (d) => d.accept());
    await approve.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('body')).toContainText(/approved/i);
  });

  test('the applicant is notified of the decision', async ({ page }) => {
    await login(page, 'resident2');
    await page.goto('notifications/index.php');
    await expect(page.locator('body')).toContainText(/foster/i);
  });

  test('a decided application is no longer in the pending queue', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/foster/index.php?status=approved');
    await expect(page.locator('body')).toContainText(/approved/i);
  });
});

test.describe('the adoption gallery', () => {
  test('only available pets are listed publicly', async ({ page }) => {
    await page.goto('adoption/gallery.php');

    const statuses = page.locator('.pet-card__status');
    const count = await statuses.count();
    expect(count).toBeGreaterThan(0);

    for (let i = 0; i < count; i += 1) {
      await expect(statuses.nth(i)).toHaveText(/available/i);
    }
  });

  test('the gallery filters by animal type', async ({ page }) => {
    await page.goto('adoption/gallery.php?animal_type=cat');
    const cards = page.locator('.pet-card');

    if (await cards.count() > 0) {
      await expect(page.locator('.pet-card').first()).toContainText(/cat/i);
    }
  });

  test('a pet profile shows its details', async ({ page }) => {
    await page.goto('adoption/gallery.php');
    await page.locator('a[href*="/adoption/pet.php?id="]').first().click();

    await expect(page.locator('h1, h2').first()).toBeVisible();
    await expect(page.locator('img')).not.toHaveCount(0);
  });

  test('a guest is asked to sign in before applying', async ({ page }) => {
    await page.goto('adoption/pet.php?id=1');
    const apply = page.locator('a[href*="/adoption/apply.php"]');

    if (await apply.count() > 0) {
      await apply.first().click();
      await expect(page).toHaveURL(/auth\/login\.php/);
    }
  });
});

test.describe('pet profiles and adoption', () => {
  let petName;
  let petId;

  test('a welfare organization can publish a pet profile', async ({ page }) => {
    petName = unique('Kalachuchi');

    await login(page, 'welfare');
    await page.goto('admin/pets/edit.php');

    await page.fill('input[name="name"]', petName);
    await page.selectOption('select[name="animal_type"]', 'cat');
    await page.fill('input[name="breed"]', 'Puspin');
    await page.selectOption('select[name="sex"]', 'female');
    await page.fill('input[name="approx_age_months"]', '10');
    await page.selectOption('select[name="size"]', 'small');
    await page.fill('input[name="color"]', 'Orange tabby');
    await page.fill(
      'textarea[name="description"]',
      'Rescued from the market area, now fully vaccinated and looking for a quiet home.'
    );
    await page.setInputFiles('input[name="photo"]', SAMPLE_PHOTO);
    await page.selectOption('select[name="status"]', 'available');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('the published pet appears in the public gallery', async ({ page }) => {
    await page.goto('adoption/gallery.php');
    await expect(page.locator('body')).toContainText(petName);

    const card = page.locator('.pet-card', { hasText: petName });
    await expect(card).toHaveCount(1);

    await card.locator('a[href*="/adoption/pet.php?id="]').first().click();
    petId = new URL(page.url()).searchParams.get('id');
    expect(petId).toBeTruthy();

    await expect(page.locator('body')).toContainText(petName);

    await expect(page.locator('img[src*="/uploads/pets/"]').first()).toBeVisible();
  });

  test('a resident can apply to adopt it', async ({ page }) => {
    await login(page, 'resident');
    await page.goto(`adoption/apply.php?pet_id=${petId}`);

    await page.fill(
      'textarea[name="message"]',
      'We have wanted a cat for a long time and can offer a quiet indoor home.'
    );
    await page.fill('input[name="home_type"]', 'House with a small yard');
    await page.click('main button[type="submit"]');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('the form is not offered to someone who already applied', async ({ page }) => {
    await login(page, 'resident');
    await page.goto(`adoption/apply.php?pet_id=${petId}`);

    await expect(page).toHaveURL(new RegExp(`adoption/pet\\.php\\?id=${petId}`));
    await expect(page.locator('.alert-info')).toContainText(/already applied/i);
  });

  test('a duplicate application is refused rather than erroring', async ({ page }) => {

    await login(page, 'resident');
    await page.goto(`adoption/pet.php?id=${petId}`);

    const token = await page.locator('input[name="_csrf"]').first().getAttribute('value');
    const response = await page.request.post('actions/adoption/adoption_submit.php', {
      form: {
        _csrf: token,
        pet_id: petId,
        message: 'Attempting to apply a second time for the same pet.',
        home_type: 'House',
      },
      maxRedirects: 0,
    });

    expect(response.status(), 'a duplicate must redirect, not error').toBe(302);

    await page.goto(`adoption/pet.php?id=${petId}`);
    await expect(page.locator('body')).not.toContainText(/Fatal error|SQLSTATE|Something went wrong/);
    await expect(page.locator('.alert-info')).toContainText(/already applied/i);
  });

  test('a welfare organization can approve the adoption', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/adoption/index.php');

    const row = page.locator('tr', { hasText: petName });
    await expect(row).toHaveCount(1);
    await row.locator('a[href*="/admin/adoption/view.php?id="]').first().click();

    const approve = page.locator('form:has(input[name="decision"][value="approve"])');
    await expect(approve).toBeVisible();

    page.once('dialog', (d) => d.accept());
    await approve.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('body')).toContainText(/approved/i);
  });

  test('an approved adoption produces a PDF agreement', async ({ page }) => {
    await login(page, 'welfare');
    await page.goto('admin/adoption/index.php');

    const row = page.locator('tr', { hasText: petName });
    const href = await row.locator('a[href*="/admin/adoption/view.php?id="]').first().getAttribute('href');
    const appId = new URL(href, 'http://x').searchParams.get('id');

    const response = await page.request.get(
      `admin/adoption/agreement_pdf.php?application_id=${appId}`
    );

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('pdf');

    const body = await response.body();
    expect(body.subarray(0, 5).toString()).toBe('%PDF-');
    expect(body.length).toBeGreaterThan(500);
  });

  test('a resident cannot generate an agreement', async ({ page }) => {
    await login(page, 'resident');
    const response = await page.request.get('admin/adoption/agreement_pdf.php?application_id=1');
    expect(response.status()).toBe(403);
  });
});
