import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchTags, deleteTag } from './tags';

describe('Tags API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchTags', () => {
    it('should fetch tags and return JSON response if successful', async () => {
      const mockResponse = [
        { id: '1', name: 'Tag1' },
        { id: '2', name: 'Tag2' },
      ];
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const searchString = 'Tag';
      const result = await fetchTags(searchString);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/content/tags?search=Tag', {
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

      const searchString = 'Tag';

      await expect(fetchTags(searchString)).rejects.toThrow(mockError);
    });
  });

  describe('deleteTag', () => {
    it('should delete tag and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Tag deleted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const tagId = '1';
      await deleteTag(tagId);

      expect(global.fetch).toHaveBeenCalledWith('./api/content/tags/1', {
        method: 'DELETE',
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

      const tagId = '1';

      await expect(deleteTag(tagId)).rejects.toThrow(mockError);
    });
  });
});
