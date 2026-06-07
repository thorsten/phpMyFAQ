import { expect, test } from '@playwright/test';
import { SEEDED, withLang } from './fixtures/seeded-data';

/**
 * Search returns the seeded FAQ in both languages.
 */
test.describe('Search (DE + EN)', () => {
  const faq = SEEDED.faqs.databases;

  test('English search finds the seeded FAQ', async ({ page }) => {
    await page.goto(withLang(`/search.html?search=${encodeURIComponent(faq.searchTerm.en)}`, 'en'));

    await expect(page.locator('li.pmf-search-result').first()).toBeVisible();
    await expect(page.getByRole('link', { name: faq.en }).first()).toBeVisible();
  });

  test('German search finds the seeded FAQ', async ({ page }) => {
    await page.goto(withLang(`/search.html?search=${encodeURIComponent(faq.searchTerm.de)}`, 'de'));

    await expect(page.locator('li.pmf-search-result').first()).toBeVisible();
    await expect(page.getByRole('link', { name: faq.de }).first()).toBeVisible();
  });
});
