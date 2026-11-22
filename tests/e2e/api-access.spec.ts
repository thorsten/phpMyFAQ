import { expect, Page, test } from '@playwright/test';
import { ADMIN } from './fixtures/seeded-data';

const login = async (page: Page): Promise<void> => {
  await page.goto('/admin/login');
  await page.locator('#faqusername').fill(ADMIN.user);
  await page.locator('#faqpassword').fill(ADMIN.password);
  await page.locator('button[type="submit"]').click();
  await expect(page.locator('#pmf-dashboard-metrics')).toBeVisible();
};

/**
 * The administration pages for the credentials of the REST API: the API keys
 * of the logged-in user, and the clients and scopes of the OAuth 2.0
 * authorization server. Both hand out a secret exactly once.
 */
test.describe('API access administration', () => {
  test('an API key is shown once after it was created and can be deleted', async ({ page }) => {
    const name = `e2e key ${Date.now()}`;

    await login(page);
    await page.goto('/admin/user/api-keys');

    await page.locator('#pmf-api-key-add').click();
    await page.locator('#pmf-api-key-name').fill(name);
    await page.locator('.pmf-api-key-scope[value="faq.read"]').check();
    await page.locator('#pmf-api-key-form button[type="submit"]').click();

    // Only the hash is stored, so this modal is the one place the key appears.
    await expect(page.locator('#pmf-modal-api-key-created')).toBeVisible();
    await expect(page.locator('#pmf-api-key-value')).toHaveValue(/^pmf_[a-f0-9]{40}$/);
    await page.locator('#pmf-modal-api-key-created .modal-footer button').click();
    await expect(page.locator('#pmf-modal-api-key-created')).toBeHidden();

    const row = page.locator('#pmf-api-keys-list tr', { hasText: name });
    await expect(row).toBeVisible();
    await expect(row).toContainText('faq.read');

    page.once('dialog', (dialog) => dialog.accept());
    await row.locator('.btn-danger').click();
    await expect(row).toHaveCount(0);
  });

  test('an OAuth 2.0 client gets its secret once, and a scope can be enabled', async ({ page }) => {
    const name = `e2e client ${Date.now()}`;

    await login(page);
    await page.goto('/admin/oauth2');

    await page.locator('#pmf-oauth2-client-add').click();
    await page.locator('#pmf-oauth2-client-name').fill(name);
    await page.locator('.pmf-oauth2-client-grant[value="client_credentials"]').check();
    await page.locator('#pmf-oauth2-client-form button[type="submit"]').click();

    await expect(page.locator('#pmf-modal-oauth2-client-created')).toBeVisible();
    await expect(page.locator('#pmf-oauth2-created-client-id')).toHaveValue(/^pmf_[a-f0-9]{32}$/);
    await expect(page.locator('#pmf-oauth2-created-client-secret')).toHaveValue(/^[a-f0-9]{64}$/);

    // Closing the modal reloads the page: the list is rendered by the server.
    await page.locator('#pmf-modal-oauth2-client-created .modal-footer button').click();
    const row = page.locator('#pmf-oauth2 tr', { hasText: name });
    await expect(row).toBeVisible();
    await expect(row).toContainText('client_credentials');

    const scope = page.locator('.pmf-oauth2-scope[data-scope="faq.read"]');
    await scope.locator('.pmf-oauth2-scope-enabled').check();
    await scope.locator('.pmf-oauth2-scope-description').fill('Read FAQs');
    const saved = page.waitForResponse((response) => response.url().endsWith('/admin/api/oauth2/scopes'));
    await page.locator('#pmf-oauth2-scopes-form button[type="submit"]').click();
    expect((await saved).status()).toBe(200);

    await page.reload();
    await expect(scope.locator('.pmf-oauth2-scope-enabled')).toBeChecked();
    await expect(scope.locator('.pmf-oauth2-scope-description')).toHaveValue('Read FAQs');

    // Leave the installation as it was found.
    await scope.locator('.pmf-oauth2-scope-enabled').uncheck();
    await page.locator('#pmf-oauth2-scopes-form button[type="submit"]').click();
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#pmf-oauth2 tr', { hasText: name }).locator('.pmf-oauth2-client-delete').click();
    await expect(page.locator('#pmf-oauth2 tr', { hasText: name })).toHaveCount(0);
  });
});
