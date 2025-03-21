import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchElasticsearchAction, fetchElasticsearchStatistics } from './elasticsearch';

describe('Elasticsearch API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchElasticsearchAction', () => {
    it('should fetch Elasticsearch action and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Action executed' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const action = 'some-action';
      const result = await fetchElasticsearchAction(action);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/elasticsearch/some-action', {
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

      const action = 'some-action';

      await expect(fetchElasticsearchAction(action)).rejects.toThrow(mockError);
    });
  });

  describe('fetchElasticsearchStatistics', () => {
    it('should fetch Elasticsearch statistics and return JSON response if successful', async () => {
      const mockResponse = {
        index: 'index-name',
        stats: {
          indices: {
            'index-name': {
              total: {
                docs: {
                  count: 100,
                },
                store: {
                  size_in_bytes: 1024,
                },
              },
            },
          },
        },
      };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchElasticsearchStatistics();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/elasticsearch/statistics', {
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

      await expect(fetchElasticsearchStatistics()).rejects.toThrow(mockError);
    });
  });
});
