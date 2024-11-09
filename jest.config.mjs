export default {
  transform: {
    '^.+\\.jsx?$': 'babel-jest',
  },
  clearMocks: true,
  collectCoverage: true,
  coverageDirectory: 'coverage',
  coverageReporters: ['text', 'lcov'],
  moduleFileExtensions: ['js', 'jsx', 'json', 'node'],
  testEnvironment: 'jsdom',
  testMatch: ['**/*.test.js', '!**/*.playwright.test.js'],
  testPathIgnorePatterns: ['/node_modules/', '/dist/'],
};
