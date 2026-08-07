
const { defineConfig, devices } = require('@playwright/test');

const HOST = process.env.E2E_HOST || '127.0.0.1';
const PORT = Number(process.env.E2E_PORT || 8100);
const BASE_PATH = process.env.BASE_URL !== undefined ? process.env.BASE_URL : '/repawter';

const BASE_URL = process.env.E2E_BASE_URL || `http://${HOST}:${PORT}${BASE_PATH}/`.replace(/\/+$/, '/');

const appEnv = {
  APP_ENV: 'dev',
  BASE_URL: BASE_PATH,
  DB_HOST: process.env.DB_HOST || '127.0.0.1',
  DB_NAME: process.env.E2E_DB_NAME || 'repawter_e2e',
  DB_USER: process.env.DB_USER || 'root',
  DB_PASS: process.env.DB_PASS || '',
};

module.exports = defineConfig({
  testDir: './tests/e2e',
  outputDir: './test-results',

  fullyParallel: false,
  workers: 1,

  retries: process.env.CI ? 1 : 0,

  forbidOnly: !!process.env.CI,

  timeout: 30_000,
  expect: { timeout: 7_000 },

  globalSetup: require.resolve('./tests/support/global-setup.js'),

  reporter: process.env.CI
    ? [['list'], ['html', { open: 'never' }], ['github']]
    : [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: BASE_URL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'off',
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 } },

      grepInvert: /@mobile/,
    },
    {

      name: 'mobile',
      use: { ...devices['Pixel 7'] },
      grep: /@mobile/,
    },
  ],

  webServer: {
    command: `php -S ${HOST}:${PORT} tests/support/router.php`,
    url: `${BASE_URL}/auth/login.php`,
    reuseExistingServer: !process.env.CI,
    timeout: 30_000,

    stdout: 'ignore',
    stderr: process.env.E2E_SERVER_LOG ? 'pipe' : 'ignore',
    env: appEnv,
  },
});

module.exports.appEnv = appEnv;
module.exports.BASE_PATH = BASE_PATH;
