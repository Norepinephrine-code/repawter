
const path = require('path');
const { expect } = require('@playwright/test');

const PASSWORD = 'Password123!';

const USERS = {
  admin:     { email: 'admin@repawter.test',          name: 'System Admin',   role: 'system_admin' },
  official:  { email: 'official.brgy1@repawter.test', name: 'Ana Reyes',      role: 'barangay_official' },
  official2: { email: 'official.brgy2@repawter.test', name: 'Ben Cruz',       role: 'barangay_official' },
  welfare:   { email: 'shelter@pawrescue.test',       name: 'Paw Rescue PH',  role: 'welfare_org' },
  welfare2:  { email: 'org@strayhaven.test',          name: 'Stray Haven',    role: 'welfare_org' },
  resident:  { email: 'juan.delacruz@example.test',   name: 'Juan Dela Cruz', role: 'community_resident' },
  resident2: { email: 'maria.santos@example.test',    name: 'Maria Santos',   role: 'community_resident' },
  flagged:   { email: 'pedro.reyes@example.test',     name: 'Pedro Reyes',    role: 'community_resident' },
};

const SAMPLE_PHOTO = path.resolve(__dirname, '..', 'support', 'fixtures', 'sample-photo.jpg');

async function login(page, who) {
  const user = typeof who === 'string' ? USERS[who] : who;
  if (!user) {
    throw new Error(`Unknown seeded user: ${who}`);
  }

  await page.goto('auth/login.php');
  await page.fill('input[name="email"]', user.email);
  await page.fill('input[name="password"]', user.password || PASSWORD);
  await page.click('main button[type="submit"]');

  await expect(page.locator('#profileDropdown')).toBeVisible();
  return user;
}

async function logout(page) {
  await page.click('#profileDropdown');
  await page.click('button:has-text("Logout")');
  await expect(page.locator('a[href$="/auth/login.php"]').first()).toBeVisible();
}

function unique(prefix) {
  return `${prefix}-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
}

async function expectNoHorizontalOverflow(page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }));

  expect(
    scrollWidth,
    `page scrolls horizontally (${scrollWidth}px of content in ${clientWidth}px)`
  ).toBeLessThanOrEqual(clientWidth + 1);
}

function collectPageProblems(page) {
  const problems = [];

  const isOwnOrigin = (url) => url.startsWith('http://127.0.0.1') || url.startsWith('http://localhost');

  page.on('console', (msg) => {
    if (msg.type() !== 'error') {
      return;
    }
    const text = msg.text();

    if (/facebook\.com/.test(text)) {
      return;
    }
    problems.push(`console: ${text}`);
  });

  page.on('pageerror', (error) => {
    problems.push(`uncaught: ${error.message}`);
  });

  page.on('requestfailed', (request) => {
    if (!isOwnOrigin(request.url())) {
      return;
    }

    const error = request.failure()?.errorText || '';
    if (error.includes('ERR_ABORTED')) {
      return;
    }

    problems.push(`request failed: ${request.url()} (${error})`);
  });

  page.on('response', (response) => {

    if (response.status() === 404 && isOwnOrigin(response.url()) && !response.url().includes('.php')) {
      problems.push(`404: ${response.url()}`);
    }
  });

  return () => problems;
}

async function expectNoPhpErrors(page) {
  const body = await page.locator('body').innerText();
  expect(body).not.toMatch(/Fatal error|Parse error|Uncaught \w*(Exception|Error)|Warning:|Notice:|Deprecated:/);
}

module.exports = {
  PASSWORD,
  USERS,
  SAMPLE_PHOTO,
  login,
  logout,
  unique,
  expectNoHorizontalOverflow,
  collectPageProblems,
  expectNoPhpErrors,
};
