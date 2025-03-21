import { describe, it, expect, vi, afterEach } from 'vitest';
import { getRemoteHashes, verifyHashes } from './verification';

describe('Verification API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('getRemoteHashes', () => {
    it('should fetch remote hashes and return JSON response if successful', async () => {
      const mockResponse = { 'file1.js': 'hash1', 'file2.js': 'hash2' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const version = '1.0.0';
      const result = await getRemoteHashes(version);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('https://api.phpmyfaq.de/verify/1.0.0', {
        method: 'GET',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const version = '1.0.0';

      await expect(getRemoteHashes(version)).rejects.toThrow(mockError);
    });
  });

  describe('verifyHashes', () => {
    it('should verify hashes and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Hashes verified' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const remoteHashes = { 'file1.js': 'hash1', 'file2.js': 'hash2' };
      const result = await verifyHashes(remoteHashes);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/dashboard/verify', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(remoteHashes),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const remoteHashes = { 'file1.js': 'hash1', 'file2.js': 'hash2' };

      await expect(verifyHashes(remoteHashes)).rejects.toThrow(mockError);
    });
  });
});
