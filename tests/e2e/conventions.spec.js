
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const SPEC_DIR = __dirname;

function specFiles() {
  return fs
    .readdirSync(SPEC_DIR)
    .filter((f) => f.endsWith('.js'))
    .map((f) => ({ name: f, source: fs.readFileSync(path.join(SPEC_DIR, f), 'utf8') }));
}

test.describe('spec conventions', () => {
  
  test('navigation paths are relative to baseURL, never absolute', () => {
    const offenders = [];

    for (const { name, source } of specFiles()) {
      if (name === 'conventions.spec.js') {
        continue;
      }

      source.split('\n').forEach((rawLine, i) => {
        if (/^\s*(\/\/|\*|\/\*)/.test(rawLine)) {
          return;
        }

        // A CSS attribute selector such as a[href*="/admin/"] matches the
        // rendered markup, where the BASE_URL prefix is genuinely present.
        // Strip those before scanning so they are not mistaken for navigation.
        const line = rawLine.replace(/\[[a-zA-Z-]+[\^$*~|]?=["'][^"']*["']\]/g, '[]');

        // goto('/x') / request.get(`/x`) - the direct form.
        const navCall = /\b(?:goto|request\.(?:get|post))\(\s*[`'"]\//;

        // A quoted app path anywhere on the line, which covers route entries
        // held in arrays and passed to goto() through a variable. Those are the
        // dangerous ones: they read as ordinary data, so the mistake is easy to
        // miss in review and produces a test that passes against the wrong page.
        const APP_DIRS = 'admin|reports|foster|adoption|announcements|resources'
          + '|notifications|auth|profile|actions|assets|uploads';
        const routeLiteral = new RegExp("[`'\"]/(?:" + APP_DIRS + ")(?:/|\\.|['\"`])");

        // A bare '/' route entry means the app root; './' is the correct form.
        const bareRoot = /(?:^|[[(,]\s*)(['"`])\/\1/;

        if (navCall.test(line) || routeLiteral.test(line) || bareRoot.test(line)) {
          offenders.push(`${name}:${i + 1}  ${rawLine.trim()}`);
        }
      });
    }

    expect(
      offenders,
      'These paths start with "/" and will lose the BASE_URL prefix and the session.\n'
      + 'Write them relative to baseURL instead — see tests/README.md.\n'
    ).toEqual([]);
  });

  test('no spec is accidentally left focused', () => {
    const offenders = [];

    for (const { name, source } of specFiles()) {
      source.split('\n').forEach((line, i) => {
        if (/\b(test|test\.describe)\.only\s*\(/.test(line)) {
          offenders.push(`${name}:${i + 1}`);
        }
      });
    }

    expect(offenders, 'test.only would silently skip the rest of the suite').toEqual([]);
  });

  test('specs use the shared login helper rather than retyping the password', () => {
    const offenders = [];

    for (const { name, source } of specFiles()) {
      if (name === 'helpers.js' || name === 'conventions.spec.js') {
        continue;
      }

      const lines = source.split('\n');
      lines.forEach((line, i) => {
        if (line.includes("'Password123!'") && !line.includes('PASSWORD')) {
          offenders.push(`${name}:${i + 1}`);
        }
      });
    }

    expect(offenders, 'import PASSWORD from ./helpers instead of hard-coding it').toEqual([]);
  });
});
