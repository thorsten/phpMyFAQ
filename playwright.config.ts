import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for the phpMyFAQ end-to-end test suite.
 *
 * The application under test is provisioned and served separately (see `bin/e2e`
 * for local Docker / php -S runs and `.github/workflows/e2e-nightly.yml` for CI).
 * The base URL is provided via the E2E_BASE_URL environment variable so the same
 * specs run unchanged against a Dockerized HTTPS stack or a plain php -S server.
 */
const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:8765';

export default defineConfig({
  testDir: './tests/e2e',
  // One worker keeps the shared, seeded database deterministic across specs.
  workers: 1,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  timeout: 30_000,
  expect: { timeout: 10_000 },
  reporter: process.env.CI
    ? [['list'], ['html', { open: 'never' }], ['github']]
    : [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    // The Dockerized stack serves HTTPS with a self-signed dev certificate.
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
