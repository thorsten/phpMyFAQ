import { describe, it, expect, vi, afterEach } from 'vitest';
import { deleteAdminLog, truncateSearchTerms, clearRatings, clearVisits, deleteSessions } from './statistics';

describe('Statistics API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('deleteAdminLog', () => {
    it('should delete admin log and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Admin log deleted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const result = await deleteAdminLog(csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/statistics/admin-log', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrfToken }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';

      await expect(deleteAdminLog(csrfToken)).rejects.toThrow(mockError);
    });
  });

  describe('truncateSearchTerms', () => {
    it('should truncate search terms and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Search terms truncated' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const result = await truncateSearchTerms(csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/statistics/search-terms', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrfToken }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';

      await expect(truncateSearchTerms(csrfToken)).rejects.toThrow(mockError);
    });
  });

  describe('clearRatings', () => {
    it('should clear ratings and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Ratings cleared' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const result = await clearRatings(csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/statistics/ratings/clear', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrfToken }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';

      await expect(clearRatings(csrfToken)).rejects.toThrow(mockError);
    });
  });

  describe('clearVisits', () => {
    it('should clear visits and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Visits cleared' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const result = await clearVisits(csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/statistics/visits/clear', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrfToken }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';

      await expect(clearVisits(csrfToken)).rejects.toThrow(mockError);
    });
  });

  describe('deleteSessions', () => {
    it('should delete sessions and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Sessions deleted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const month = '2024-04';
      const result = await deleteSessions(csrfToken, month);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/statistics/sessions', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrfToken, month }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';
      const month = '2024-04';

      await expect(deleteSessions(csrfToken, month)).rejects.toThrow(mockError);
    });
  });
});
