import { expect, test } from '@playwright/test';
import { ADMIN } from './fixtures/seeded-data';

/**
 * Admin can authenticate with the installed account and reach the dashboard.
 */
test.describe('Admin backend', () => {
  test('login with the installed admin account reaches the dashboard', async ({ page }) => {
    await page.goto('/admin/login');

    await page.locator('#faqusername').fill(ADMIN.user);
    await page.locator('#faqpassword').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();

    // The dashboard metrics container only renders for an authenticated admin.
    await expect(page.locator('#pmf-dashboard-metrics')).toBeVisible();
    // The dashboard heading carries a language-independent speedometer icon
    // (also present in the sidebar nav, hence .first()).
    await expect(page.locator('i.bi-speedometer').first()).toBeVisible();
  });

  test('invalid credentials are rejected', async ({ page }) => {
    await page.goto('/admin/login');

    await page.locator('#faqusername').fill(ADMIN.user);
    await page.locator('#faqpassword').fill('definitely-the-wrong-password');
    await page.locator('button[type="submit"]').click();

    // We must not end up on the authenticated dashboard.
    await expect(page.locator('i.bi-speedometer')).toHaveCount(0);
  });
});
