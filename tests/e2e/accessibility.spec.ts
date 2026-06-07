import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { withLang } from './fixtures/seeded-data';

type AxeBuilderPage = ConstructorParameters<typeof AxeBuilder>[0]['page'];

/**
 * Accessibility smoke checks. We gate the build on critical/serious violations
 * only; less severe findings are surfaced in the report without failing CI.
 */
async function scan(page: import('@playwright/test').Page) {
  return new AxeBuilder({ page: page as unknown as AxeBuilderPage }).withTags(['wcag2a', 'wcag2aa']).analyze();
}

test.describe('Accessibility smoke', () => {
  test('home page has no critical or serious violations', async ({ page }) => {
    await page.goto(withLang('/', 'en'));
    const results = await scan(page);
    const blocking = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
    expect(
      blocking,
      JSON.stringify(
        blocking.map((v) => ({ id: v.id, impact: v.impact })),
        null,
        2
      )
    ).toEqual([]);
  });

  test('category overview has no critical or serious violations', async ({ page }) => {
    await page.goto(withLang('/show-categories.html', 'en'));
    const results = await scan(page);
    const blocking = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
    expect(
      blocking,
      JSON.stringify(
        blocking.map((v) => ({ id: v.id, impact: v.impact })),
        null,
        2
      )
    ).toEqual([]);
  });
});
