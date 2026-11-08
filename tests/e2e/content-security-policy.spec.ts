import { expect, type Page, test } from '@playwright/test';
import { ADMIN } from './fixtures/seeded-data';

/**
 * The Content-Security-Policy allows no 'unsafe-inline' for scripts: every inline script carries a
 * per-request nonce instead. Only a browser can prove that, so these specs load the real pages and
 * fail on any policy violation the browser reports.
 */

type Violation = { directive: string; sample: string };

/** Records every CSP violation the browser reports, plus the matching console errors. */
const collectViolations = async (page: Page): Promise<Violation[]> => {
  const violations: Violation[] = [];

  await page.exposeFunction('pmfReportViolation', (directive: string, sample: string): void => {
    violations.push({ directive, sample });
  });
  await page.addInitScript((): void => {
    document.addEventListener('securitypolicyviolation', (event: SecurityPolicyViolationEvent): void => {
      void (window as unknown as { pmfReportViolation: (d: string, s: string) => void }).pmfReportViolation(
        event.effectiveDirective,
        event.sourceFile === null ? event.blockedURI : `${event.sourceFile}:${event.lineNumber}`
      );
    });
  });

  return violations;
};

const scriptSourceOf = async (page: Page, path: string): Promise<string> => {
  const response = await page.goto(path);
  const csp = (await response?.headerValue('content-security-policy')) ?? '';
  const directive = csp
    .split(';')
    .map((part: string): string => part.trim())
    .find((part: string): boolean => part.startsWith('script-src '));

  expect(directive, `no script-src directive on ${path}`).toBeDefined();

  return directive as string;
};

test.describe('Content-Security-Policy', () => {
  test('the public start page reports no policy violation', async ({ page }) => {
    const violations = await collectViolations(page);

    await page.goto('/');
    await page.waitForLoadState('networkidle');

    expect(violations).toEqual([]);
  });

  test('script-src carries a nonce and no unsafe directive', async ({ page }) => {
    const scriptSource = await scriptSourceOf(page, '/');

    expect(scriptSource).toMatch(/'nonce-[A-Za-z0-9+/]+={0,2}'/);
    expect(scriptSource).not.toContain("'unsafe-inline'");
    expect(scriptSource).not.toContain("'unsafe-eval'");
  });

  test('every inline script on the start page carries the nonce from the header', async ({ page }) => {
    const scriptSource = await scriptSourceOf(page, '/');
    const nonce = /'nonce-([^']+)'/.exec(scriptSource)?.[1];

    const inlineScripts = await page
      .locator('script:not([src])')
      .evaluateAll((elements: Element[]): string[] =>
        elements.map((element: Element): string => element.getAttribute('nonce') ?? '')
      );

    expect(inlineScripts.length).toBeGreaterThan(0);
    // The browser hides the attribute value from the DOM, so a bound nonce reads as an empty string;
    // what matters is that the nonce property still holds the header value.
    const nonceProperties = await page
      .locator('script:not([src])')
      .evaluateAll((elements: Element[]): string[] =>
        elements.map((element: Element): string => (element as HTMLScriptElement).nonce ?? '')
      );

    expect(nonce).toBeDefined();
    expect(new Set(nonceProperties)).toEqual(new Set([nonce]));
  });

  test('two requests never share a nonce', async ({ page }) => {
    const first = await scriptSourceOf(page, '/');
    const second = await scriptSourceOf(page, '/?cache-buster=1');

    expect(first).not.toBe(second);
  });

  test('the admin dashboard reports no policy violation', async ({ page }) => {
    const violations = await collectViolations(page);

    await page.goto('/admin/login');
    await page.locator('#faqusername').fill(ADMIN.user);
    await page.locator('#faqpassword').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await expect(page.locator('#pmf-dashboard-metrics')).toBeVisible();
    await page.waitForLoadState('networkidle');

    expect(violations).toEqual([]);
  });
});
