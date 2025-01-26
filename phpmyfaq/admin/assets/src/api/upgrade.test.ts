import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchHealthCheck } from './upgrade';

describe('Upgrade API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchHealthCheck', () => {
    it('should fetch health check and return JSON response if successful', async () => {
      const mockResponse = { success: 'true', message: 'Health check passed' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchHealthCheck();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/health-check', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchHealthCheck()).rejects.toThrow(mockError);
    });
  });
});
