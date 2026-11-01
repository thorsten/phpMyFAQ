import { expect, test } from '@playwright/test';
import { ADMIN } from './fixtures/seeded-data';

/**
 * The FAQ answer editor keeps its toolbar reachable while a long answer is
 * scrolled. The admin shell scrolls inside #pmf-admin-layout-sidenav rather than
 * the window, which is why this is covered end-to-end instead of in a unit test.
 */
test.describe('FAQ editor', () => {
  test('toolbar stays visible while scrolling through an enlarged editor', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('#faqusername').fill(ADMIN.user);
    await page.locator('#faqpassword').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await expect(page.locator('#pmf-dashboard-metrics')).toBeVisible();

    await page.goto('/admin/faq/add');
    const container = page.locator('.jodit-container').first();
    const toolbar = container.locator('> .jodit-toolbar__box');
    await expect(toolbar).toBeVisible();

    // Same effect as dragging Jodit's resize handle to make the editor huge.
    await container.evaluate((el) => {
      el.style.height = '2500px';
    });
    const scrollArea = page.locator('#pmf-admin-layout-sidenav');
    const areaTop = (await scrollArea.boundingBox())!.y;
    await scrollArea.evaluate((el) => {
      el.scrollTop = 900;
    });

    const containerBox = (await container.boundingBox())!;
    const toolbarBox = (await toolbar.boundingBox())!;
    // The editor has scrolled off the top, the toolbar is pinned to the scroll area.
    expect(containerBox.y).toBeLessThan(areaTop);
    expect(Math.round(toolbarBox.y)).toBe(Math.round(areaTop));
  });
});
