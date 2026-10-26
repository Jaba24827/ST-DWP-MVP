
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  // Playwright test files
  testDir: './e2e',

  globalSetup: './e2e/global-setup.ts',

  // Run sequentially initially for reliable database-dependent tests
  fullyParallel: false,

  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,

  // Console output and HTML report
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
  ],

  // Shared settings for all browser projects
  use: {
    // Dedicated Laravel E2E server
    baseURL: 'http://127.0.0.1:8001',

    // Capture debugging evidence
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 10_000,
    navigationTimeout: 30_000,
  },

  // Start with Chromium to simplify initial setup
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    // Enable these later if cross-browser testing is needed
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },
  ],

  // Start Laravel with the testing environment.
  // The server must load .env.testing and use sldwp_e2e.
  webServer: {
    command:
      'php artisan serve --env=testing --host=127.0.0.1 --port=8001',
    url: 'http://127.0.0.1:8001',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    stdout: 'pipe',
    stderr: 'pipe',
  },
});
