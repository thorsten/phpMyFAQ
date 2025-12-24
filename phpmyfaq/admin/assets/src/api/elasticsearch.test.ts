import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchElasticsearchAction, fetchElasticsearchStatistics, fetchElasticsearchHealthcheck } from './elasticsearch';

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

  describe('fetchElasticsearchHealthcheck', () => {
    it('should fetch Elasticsearch healthcheck and return JSON response when available', async () => {
      const mockResponse = { available: true, status: 'healthy' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchElasticsearchHealthcheck();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/elasticsearch/healthcheck', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error when Elasticsearch returns 503 Service Unavailable', async () => {
      const errorResponse = { available: false, status: 'unavailable' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 503,
          json: () => Promise.resolve(errorResponse),
        } as Response)
      );

      await expect(fetchElasticsearchHealthcheck()).rejects.toThrow('Elasticsearch is unavailable');
    });

    it('should throw an error with custom message when error data is provided', async () => {
      const errorResponse = { error: 'Connection refused' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 503,
          json: () => Promise.resolve(errorResponse),
        } as Response)
      );

      await expect(fetchElasticsearchHealthcheck()).rejects.toThrow('Connection refused');
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Network error');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchElasticsearchHealthcheck()).rejects.toThrow(mockError);
    });
  });
});
