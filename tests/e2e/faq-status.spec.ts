import { expect, test } from '@playwright/test';
import { ADMIN, SEEDED } from './fixtures/seeded-data';

/**
 * The editorial status workflow (draft / review / published), driven through
 * the same endpoint the admin FAQ overview's per-row status <select> posts to
 * (`POST ./api/faq/status`, see phpmyfaq/admin/assets/src/content/faqs.overview.ts)
 * rather than through the UI control itself.
 *
 * The admin FAQ editor is a rich-text (Jodit) form with a category tree
 * widget, which is expensive to drive reliably from a spec; exercising the
 * status endpoint plus the public read API directly covers the same
 * acceptance criteria (status changes are permission-checked, published
 * content is distinguishable, status is respected by the API) without that
 * extra UI surface.
 *
 * Cookie-handling constraints shape how requests are issued here. The admin
 * session cookie is set with the `Secure` attribute unconditionally
 * (phpMyFAQ\Bootstrap\PhpConfigurator::configureSession hard-codes
 * `session.cookie_secure=1`) while the e2e harness serves plain HTTP. A
 * browser still sends `Secure` cookies to `localhost`/`127.0.0.1` under the
 * "potentially trustworthy origin" exemption, but every Playwright HTTP
 * client outside the browser (`request` AND `page.request`) drops them over
 * plain http — so the authenticated status POST must run as an in-page
 * fetch. The anonymous public API reads use the standalone `request`
 * fixture on purpose: its isolated cookie jar means the anonymous session
 * cookie those responses set can never clobber the browser's admin session.
 */
test.describe('FAQ editorial status workflow', () => {
  const faq = SEEDED.faqs.upgradeGuide;

  test('a draft FAQ disappears from the public API and reappears once published', async ({ page, request }) => {
    // 1. Log in exactly like the admin login spec does, so the browser holds
    // a real, usable session cookie.
    await page.goto('/admin/login');
    await page.locator('#faqusername').fill(ADMIN.user);
    await page.locator('#faqpassword').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await expect(page.locator('#pmf-dashboard-metrics')).toBeVisible();

    // 2. The status-change endpoint checks a page-scoped, double-submit CSRF
    // token (session value + cookie, see phpMyFAQ\Session\Token). The admin
    // FAQ overview page renders the session-side value server-side into
    // every category's tbody (`csrfTokenOverview` in faq.overview.twig) and
    // sets the matching cookie as a side effect of loading it, so visiting
    // the page once is enough to obtain a token the browser can then submit.
    await page.goto('/admin/faqs');
    const csrf = await page.locator('[data-pmf-csrf]').first().getAttribute('data-pmf-csrf');
    expect(csrf, 'CSRF token not found on the admin FAQ overview page').not.toBeNull();

    // 3. Locate the seeded FAQ's id via the public listing API (no auth
    // required) rather than hard-coding a database id.
    const listResponse = await request.get('/api/v4.0/faqs?per_page=100&sort=id&order=asc', {
      headers: { 'Accept-Language': 'en' },
    });
    expect(listResponse.ok()).toBeTruthy();
    const listBody = (await listResponse.json()) as {
      data: Array<{ id: number; category_id: number; title: string; status: string }>;
    };
    const seededFaq = listBody.data.find((entry) => entry.title === faq.en);
    expect(seededFaq, `Seeded FAQ "${faq.en}" not found via the public API`).toBeDefined();
    expect(seededFaq?.status).toBe('published');

    const faqId = (seededFaq as { id: number }).id;
    const categoryId = (seededFaq as { category_id: number }).category_id;

    // The status POST must go through the browser (in-page fetch), not
    // page.request: the app marks its session cookie Secure, which browsers
    // still send to the trustworthy origin http://127.0.0.1 but Playwright's
    // separate request stack refuses to attach over plain http — the call
    // would arrive session-less and 401. This also mirrors how the admin UI
    // itself invokes the endpoint.
    const postStatus = async (status: string): Promise<{ ok: boolean; body: { success?: string; error?: string } }> =>
      await page.evaluate(
        async (payload) => {
          const response = await fetch('/admin/api/faq/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });
          return { ok: response.ok, body: (await response.json()) as { success?: string; error?: string } };
        },
        { csrf, categoryId, faqIds: [faqId], faqLanguage: 'en', status }
      );

    const setStatus = async (status: string): Promise<void> => {
      const result = await postStatus(status);
      expect(result.ok, `Failed to set status to "${status}"`).toBeTruthy();
      expect(result.body.success, result.body.error).toBeTruthy();
    };

    let fixtureRestored = false;

    try {
      // 4. Draft: the public "by id" endpoint always scopes to published
      // content (phpMyFAQ\Faq::getFaqByIdAndCategoryId defaults to
      // onlyActive=true) and must now 404 — the acceptance criterion that a
      // draft is not visible on the read surface.
      await setStatus('draft');
      const draftResponse = await request.get(`/api/v4.0/faq/${categoryId}/${faqId}`, {
        headers: { 'Accept-Language': 'en' },
      });
      expect(draftResponse.status()).toBe(404);

      // 5. Published again: the FAQ is visible once more, with the `status`
      // field in the REST response reflecting the change.
      await setStatus('published');
      fixtureRestored = true;
      const publishedResponse = await request.get(`/api/v4.0/faq/${categoryId}/${faqId}`, {
        headers: { 'Accept-Language': 'en' },
      });
      expect(publishedResponse.ok()).toBeTruthy();
      const publishedBody = (await publishedResponse.json()) as { status: string };
      expect(publishedBody.status).toBe('published');
    } finally {
      // Leave the seeded fixture published for later specs and reruns — but only
      // when an earlier failure skipped the in-test restoration, and without
      // asserting, so a cleanup hiccup cannot shadow the original test failure.
      if (!fixtureRestored) {
        await postStatus('published').catch(() => {
          // Best-effort cleanup; the test outcome is already decided.
        });
      }
    }
  });
});
