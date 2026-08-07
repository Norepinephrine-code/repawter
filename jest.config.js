

module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests/unit'],
  testRegex: '\\.test\\.js$',
  clearMocks: true,
  restoreMocks: true,

  collectCoverageFrom: [
    'assets/js/app.js',
    'assets/js/calendar-init.js'
  ],
  coverageDirectory: 'coverage',
  coverageReporters: ['text-summary', 'lcov'],

  coverageThreshold: {
    global: {
      statements: 85,
      branches: 80,
      functions: 90,
      lines: 85
    }
  }
};
