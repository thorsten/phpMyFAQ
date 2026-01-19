import { describe, it, expect, vi, afterEach } from 'vitest';
import { getRemoteHashes, verifyHashes } from './verification';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

describe('Verification API', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('getRemoteHashes', () => {
    it('should fetch remote hashes and return JSON response if successful', async () => {
      const mockResponse = { 'file1.js': 'hash1', 'file2.js': 'hash2' };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const version = '1.0.0';
      const result = await getRemoteHashes(version);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('https://api.phpmyfaq.de/verify/1.0.0', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      const version = '1.0.0';

      await expect(getRemoteHashes(version)).rejects.toThrow(mockError);
    });
  });

  describe('verifyHashes', () => {
    it('should verify hashes and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Hashes verified' };
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

      const remoteHashes = { 'file1.js': 'hash1', 'file2.js': 'hash2' };
      const result = await verifyHashes(remoteHashes);

      expect(result).toEqual(mockResponse);
      expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/dashboard/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(remoteHashes),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(mockError);

      const remoteHashes = { 'file1.js': 'hash1', 'file2.js': 'hash2' };

      await expect(verifyHashes(remoteHashes)).rejects.toThrow(mockError);
    });
  });
});
