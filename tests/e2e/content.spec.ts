import { expect, test } from '@playwright/test';
import { SEEDED, withLang } from './fixtures/seeded-data';

/**
 * Smoke + bilingual content checks against the seeded test data.
 *
 * FAQ pages are reached via the search results (a stable, ID-independent path)
 * rather than hard-coded /content/{cat}/{faq} URLs.
 */
test.describe('Frontend content (DE + EN)', () => {
  test('home page loads', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('form#search')).toBeVisible();
  });

  test('category overview lists the seeded category in English', async ({ page }) => {
    await page.goto(withLang('/show-categories.html', 'en'));
    await expect(page.getByText(SEEDED.categories.gettingStarted.en, { exact: false }).first()).toBeVisible();
  });

  test('category overview lists the seeded category in German', async ({ page }) => {
    await page.goto(withLang('/show-categories.html', 'de'));
    await expect(page.getByText(SEEDED.categories.gettingStarted.de, { exact: false }).first()).toBeVisible();
  });

  test('a seeded FAQ opens and renders question and answer (EN)', async ({ page }) => {
    const faq = SEEDED.faqs.databases;
    await page.goto(withLang(`/search.html?search=${encodeURIComponent(faq.searchTerm.en)}`, 'en'));

    await page.getByRole('link', { name: faq.en }).first().click();

    await expect(page.getByRole('heading', { name: faq.en })).toBeVisible();
    await expect(page.locator('article.pmf-faq-body')).toContainText(faq.answerFragment);
  });

  test('the seeded FAQ renders in German', async ({ page }) => {
    // The FAQ detail page follows the site default language, so the German
    // variant is asserted where the app serves localized content: the German
    // search results render the German question and its German answer preview.
    const faq = SEEDED.faqs.databases;
    await page.goto(withLang(`/search.html?search=${encodeURIComponent(faq.searchTerm.de)}`, 'de'));

    const result = page.locator('li.pmf-search-result').first();
    await expect(result.getByRole('link', { name: faq.de })).toBeVisible();
    await expect(result).toContainText(faq.answerFragment);
  });
});
