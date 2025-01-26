import { describe, it, expect, vi, afterEach } from 'vitest';
import { addInstance, deleteInstance } from './instance';

describe('Instance API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('addInstance', () => {
    it('should add instance and return JSON response if successful', async () => {
      const mockResponse = { added: '123', url: 'http://example.com', deleted: '' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrf = 'csrfToken';
      const url = 'http://example.com';
      const instance = 'instanceName';
      const comment = 'comment';
      const email = 'email@example.com';
      const admin = 'admin';
      const password = 'password';
      const result = await addInstance(csrf, url, instance, comment, email, admin, password);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/faq/search', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrf,
          url: url,
          instance: instance,
          comment: comment,
          email: email,
          admin: admin,
          password: password,
        }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrf = 'csrfToken';
      const url = 'http://example.com';
      const instance = 'instanceName';
      const comment = 'comment';
      const email = 'email@example.com';
      const admin = 'admin';
      const password = 'password';

      await expect(addInstance(csrf, url, instance, comment, email, admin, password)).rejects.toThrow(mockError);
    });
  });

  describe('deleteInstance', () => {
    it('should delete instance and return JSON response if successful', async () => {
      const mockResponse = { added: '', url: '', deleted: '123' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrf = 'csrfToken';
      const instanceId = '123';
      const result = await deleteInstance(csrf, instanceId);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/instance/delete', {
        method: 'DELETE',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrf,
          instanceId: instanceId,
        }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrf = 'csrfToken';
      const instanceId = '123';

      await expect(deleteInstance(csrf, instanceId)).rejects.toThrow(mockError);
    });
  });
});
