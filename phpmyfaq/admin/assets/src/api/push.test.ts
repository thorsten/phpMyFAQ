import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchGenerateVapidKeys } from './push';

describe('Push API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchGenerateVapidKeys', () => {
    it('should generate VAPID keys and return JSON response if successful', async () => {
      const mockResponse = { success: true, publicKey: 'BPubKey123' };
      globalThis.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchGenerateVapidKeys('csrf-token');

      expect(result).toEqual(mockResponse);
      expect(globalThis.fetch).toHaveBeenCalledWith('./api/push/generate-vapid-keys', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ csrf: 'csrf-token' }),
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      globalThis.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchGenerateVapidKeys('csrf-token')).rejects.toThrow(mockError);
    });
  });
});
