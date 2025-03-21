import { describe, it, expect, vi, afterEach } from 'vitest';
import { addNews, deleteNews, updateNews, activateNews } from './news';

describe('News API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('addNews', () => {
    it('should add news and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'News added' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const data = { title: 'Test News' };
      const result = await addNews(data);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('api/news/create', {
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

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const data = { title: 'Test News' };

      await expect(addNews(data)).rejects.toThrow(mockError);
    });
  });

  describe('deleteNews', () => {
    it('should delete news and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'News deleted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrfToken = 'csrfToken';
      const id = '123';
      const result = await deleteNews(csrfToken, id);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('api/news/delete', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({ csrfToken, id }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrfToken = 'csrfToken';
      const id = '123';

      await expect(deleteNews(csrfToken, id)).rejects.toThrow(mockError);
    });
  });

  describe('updateNews', () => {
    it('should update news and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'News updated' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const data = { id: '123', title: 'Updated News' };
      const result = await updateNews(data);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('api/news/update', {
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

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const data = { id: '123', title: 'Updated News' };

      await expect(updateNews(data)).rejects.toThrow(mockError);
    });
  });

  describe('activateNews', () => {
    it('should activate news and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'News activated' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const id = '123';
      const status = 'active';
      const csrfToken = 'csrfToken';
      const result = await activateNews(id, status, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('api/news/activate', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({ id, status, csrfToken }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const id = '123';
      const status = 'active';
      const csrfToken = 'csrfToken';

      await expect(activateNews(id, status, csrfToken)).rejects.toThrow(mockError);
    });
  });
});
