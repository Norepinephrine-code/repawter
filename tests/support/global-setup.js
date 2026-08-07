
const { execFileSync } = require('child_process');
const path = require('path');

module.exports = async function globalSetup(config) {
  const projectRoot = path.resolve(__dirname, '..', '..');
  const env = { ...process.env, ...(require('../../playwright.config.js').appEnv || {}) };

  process.stdout.write('\n[e2e] resetting test database…\n');

  try {
    const output = execFileSync(
      'php',
      [path.join('tests', 'support', 'reset-db.php')],
      { cwd: projectRoot, env, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }
    );
    process.stdout.write(
      output.split('\n').filter(Boolean).map((l) => `[e2e] ${l}`).join('\n') + '\n\n'
    );
  } catch (error) {

    process.stderr.write('\n[e2e] database reset FAILED\n');
    if (error.stdout) process.stderr.write(error.stdout.toString());
    if (error.stderr) process.stderr.write(error.stderr.toString());
    process.stderr.write(
      `\n[e2e] The database "${env.DB_NAME}" must exist and be reachable at ` +
      `${env.DB_HOST} as "${env.DB_USER}".\n` +
      '[e2e] See tests/README.md for setup.\n\n'
    );
    throw new Error('E2E database reset failed');
  }
};
