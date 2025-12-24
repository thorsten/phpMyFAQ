import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchOpenSearchAction, fetchOpenSearchStatistics, fetchOpenSearchHealthcheck } from './opensearch';

describe('OpenSearch API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchOpenSearchAction', () => {
    it('should fetch OpenSearch action and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Action executed' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const action = 'index';
      const result = await fetchOpenSearchAction(action);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/opensearch/index', {
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

      await expect(fetchOpenSearchAction('index')).rejects.toThrow(mockError);
    });
  });

  describe('fetchOpenSearchStatistics', () => {
    it('should fetch OpenSearch statistics and return JSON response if successful', async () => {
      const mockResponse = {
        status: 'green',
        documents: 123,
        indices: 5,
        size: '10GB',
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchOpenSearchStatistics();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/opensearch/statistics', {
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

      await expect(fetchOpenSearchStatistics()).rejects.toThrow(mockError);
    });
  });

  describe('fetchOpenSearchHealthcheck', () => {
    it('should fetch OpenSearch healthcheck and return JSON response when available', async () => {
      const mockResponse = { available: true, status: 'healthy' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchOpenSearchHealthcheck();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/opensearch/healthcheck', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error when OpenSearch returns 503 Service Unavailable', async () => {
      const errorResponse = { available: false, status: 'unavailable' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 503,
          json: () => Promise.resolve(errorResponse),
        } as Response)
      );

      await expect(fetchOpenSearchHealthcheck()).rejects.toThrow('OpenSearch is unavailable');
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

      await expect(fetchOpenSearchHealthcheck()).rejects.toThrow('Connection refused');
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Network error');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchOpenSearchHealthcheck()).rejects.toThrow(mockError);
    });
  });
});
