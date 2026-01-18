import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { updateStickyFaqsOrder, removeStickyFaq } from './sticky-faqs';
import * as fetchWrapperModule from './fetch-wrapper';

// Mock the fetch-wrapper module
vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

describe('sticky-faqs API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('updateStickyFaqsOrder', () => {
    it('should update sticky FAQ order successfully', async () => {
      const mockResponse = {
        success: 'Order updated successfully',
      };

      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const faqIds = ['1', '2', '3'];
      const csrf = 'test-csrf-token';

      const result = await updateStickyFaqsOrder(faqIds, csrf);

      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/faqs/sticky/order', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          faqIds,
          csrf,
        }),
      });

      expect(result).toEqual(mockResponse);
    });

    it('should handle empty FAQ IDs array', async () => {
      const mockResponse = {
        success: 'Order updated',
      };

      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const result = await updateStickyFaqsOrder([], 'csrf-token');

      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith(
        './api/faqs/sticky/order',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({
            faqIds: [],
            csrf: 'csrf-token',
          }),
        })
      );

      expect(result).toEqual(mockResponse);
    });

    it('should handle API error response', async () => {
      const mockError = new Error('API Error');

      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(updateStickyFaqsOrder(['1'], 'csrf-token')).rejects.toThrow('API Error');
    });

    it('should send correct request body format', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      await updateStickyFaqsOrder(['10', '20', '30'], 'my-csrf');

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const requestBody = JSON.parse(callArgs[1]?.body as string);

      expect(requestBody).toEqual({
        faqIds: ['10', '20', '30'],
        csrf: 'my-csrf',
      });
    });
  });

  describe('removeStickyFaq', () => {
    it('should remove sticky FAQ successfully', async () => {
      const mockResponse = {
        success: 'FAQ removed from sticky list',
      };

      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const faqId = '42';
      const categoryId = '5';
      const csrfToken = 'test-csrf-token';
      const lang = 'en';

      const result = await removeStickyFaq(faqId, categoryId, csrfToken, lang);

      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/faq/sticky', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-PMF-CSRF-Token': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          csrf: csrfToken,
          categoryId: categoryId,
          faqIds: [faqId],
          faqLanguage: lang,
          checked: false,
        }),
      });

      expect(result).toEqual(mockResponse);
    });

    it('should include correct headers', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      await removeStickyFaq('1', '2', 'my-token', 'de');

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const headers = callArgs[1]?.headers as Record<string, string>;

      expect(headers['X-PMF-CSRF-Token']).toBe('my-token');
      expect(headers['X-Requested-With']).toBe('XMLHttpRequest');
      expect(headers['Content-Type']).toBe('application/json');
      expect(headers['Accept']).toBe('application/json');
    });

    it('should send faqId as array in request body', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      await removeStickyFaq('123', '456', 'csrf', 'fr');

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const requestBody = JSON.parse(callArgs[1]?.body as string);

      expect(requestBody.faqIds).toEqual(['123']);
      expect(Array.isArray(requestBody.faqIds)).toBe(true);
    });

    it('should set checked to false', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      await removeStickyFaq('1', '2', 'csrf', 'en');

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const requestBody = JSON.parse(callArgs[1]?.body as string);

      expect(requestBody.checked).toBe(false);
    });

    it('should handle API error response', async () => {
      const mockError = new Error('Network error');

      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(removeStickyFaq('1', '2', 'csrf', 'en')).rejects.toThrow('Network error');
    });

    it('should handle different language codes', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      await removeStickyFaq('1', '2', 'csrf', 'de');

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const requestBody = JSON.parse(callArgs[1]?.body as string);

      expect(requestBody.faqLanguage).toBe('de');
    });

    it('should send all required parameters in request body', async () => {
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'OK' });

      const faqId = '999';
      const categoryId = '888';
      const csrf = 'token-123';
      const lang = 'es';

      await removeStickyFaq(faqId, categoryId, csrf, lang);

      const callArgs = vi.mocked(fetchWrapperModule.fetchJson).mock.calls[0];
      const requestBody = JSON.parse(callArgs[1]?.body as string);

      expect(requestBody).toEqual({
        csrf: csrf,
        categoryId: categoryId,
        faqIds: [faqId],
        faqLanguage: lang,
        checked: false,
      });
    });
  });
});
