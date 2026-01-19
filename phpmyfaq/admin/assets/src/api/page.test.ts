import { describe, it, expect, vi, beforeEach } from 'vitest';
import { addPage, deletePage, updatePage, activatePage, checkSlug } from './page';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

describe('Page API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('addPage', () => {
    it('should add a page and return JSON response if successful', async () => {
      const mockResponse = { success: true, id: '123' };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const data = {
        title: 'Test Page',
        content: 'Test content',
        lang: 'en',
      };

      const result = await addPage(data);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/create', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify(data),
      });
    });

    it('should handle empty data object', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const result = await addPage();

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/create', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({}),
      });
    });

    it('should throw an error if the request fails', async () => {
      const mockError = new Error('Failed to add page');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(addPage({ title: 'Test' })).rejects.toThrow('Failed to add page');
    });
  });

  describe('deletePage', () => {
    it('should delete a page and return JSON response if successful', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const csrfToken = 'token123';
      const id = '456';
      const lang = 'en';

      const result = await deletePage(csrfToken, id, lang);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/delete', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          csrfToken: csrfToken,
          id: id,
          lang: lang,
        }),
      });
    });

    it('should throw an error if the request fails', async () => {
      const mockError = new Error('Failed to delete page');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(deletePage('token', '123', 'en')).rejects.toThrow('Failed to delete page');
    });
  });

  describe('updatePage', () => {
    it('should update a page and return JSON response if successful', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const data = {
        id: '123',
        title: 'Updated Page',
        content: 'Updated content',
        lang: 'en',
      };

      const result = await updatePage(data);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/update', {
        method: 'PUT',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify(data),
      });
    });

    it('should handle empty data object', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const result = await updatePage();

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/update', {
        method: 'PUT',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({}),
      });
    });

    it('should throw an error if the request fails', async () => {
      const mockError = new Error('Failed to update page');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(updatePage({ id: '123' })).rejects.toThrow('Failed to update page');
    });
  });

  describe('activatePage', () => {
    it('should activate a page and return JSON response if successful', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const id = '123';
      const status = true;
      const csrfToken = 'token123';

      const result = await activatePage(id, status, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/activate', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          id: id,
          status: status,
          csrfToken: csrfToken,
        }),
      });
    });

    it('should deactivate a page when status is false', async () => {
      const mockResponse = { success: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const id = '123';
      const status = false;
      const csrfToken = 'token123';

      const result = await activatePage(id, status, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/activate', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          id: id,
          status: status,
          csrfToken: csrfToken,
        }),
      });
    });

    it('should throw an error if the request fails', async () => {
      const mockError = new Error('Failed to activate page');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(activatePage('123', true, 'token')).rejects.toThrow('Failed to activate page');
    });
  });

  describe('checkSlug', () => {
    it('should check slug availability and return JSON response if successful', async () => {
      const mockResponse = { available: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const slug = 'test-page';
      const lang = 'en';
      const csrfToken = 'token123';

      const result = await checkSlug(slug, lang, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/check-slug', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          slug: slug,
          lang: lang,
          csrfToken: csrfToken,
          excludeId: undefined,
        }),
      });
    });

    it('should check slug with excludeId parameter', async () => {
      const mockResponse = { available: true };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const slug = 'test-page';
      const lang = 'en';
      const csrfToken = 'token123';
      const excludeId = '456';

      const result = await checkSlug(slug, lang, csrfToken, excludeId);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/page/check-slug', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          slug: slug,
          lang: lang,
          csrfToken: csrfToken,
          excludeId: excludeId,
        }),
      });
    });

    it('should return slug not available when it already exists', async () => {
      const mockResponse = { available: false, message: 'Slug already exists' };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const result = await checkSlug('existing-slug', 'en', 'token');

      expect(result).toEqual(mockResponse);
      expect((result as { available: boolean }).available).toBe(false);
    });

    it('should throw an error if the request fails', async () => {
      const mockError = new Error('Failed to check slug');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      await expect(checkSlug('test', 'en', 'token')).rejects.toThrow('Failed to check slug');
    });
  });
});
