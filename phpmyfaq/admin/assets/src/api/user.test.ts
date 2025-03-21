import { describe, it, expect, vi, afterEach } from 'vitest';
import {
  fetchUsers,
  fetchUserData,
  fetchUserRights,
  fetchAllUsers,
  overwritePassword,
  postUserData,
  deleteUser,
} from './user';

describe('User API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('fetchUsers', () => {
    it('should fetch users and return JSON response if successful', async () => {
      const mockResponse = { success: true, data: [{ id: '1', name: 'User1' }] };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const userName = 'User';
      const result = await fetchUsers(userName);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/users?filter=User', {
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

      const userName = 'User';

      await expect(fetchUsers(userName)).rejects.toThrow(mockError);
    });
  });

  describe('fetchUserData', () => {
    it('should fetch user data and return JSON response if successful', async () => {
      const mockResponse = { success: true, data: { id: '1', name: 'User1' } };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const userId = '1';
      const result = await fetchUserData(userId);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/data/1', {
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

      const userId = '1';

      await expect(fetchUserData(userId)).rejects.toThrow(mockError);
    });
  });

  describe('fetchUserRights', () => {
    it('should fetch user rights and return JSON response if successful', async () => {
      const mockResponse = { success: true, data: { id: '1', rights: ['read', 'write'] } };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const userId = '1';
      const result = await fetchUserRights(userId);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/permissions/1', {
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

      const userId = '1';

      await expect(fetchUserRights(userId)).rejects.toThrow(mockError);
    });
  });

  describe('fetchAllUsers', () => {
    it('should fetch all users and return JSON response if successful', async () => {
      const mockResponse = { success: true, data: [{ id: '1', name: 'User1' }] };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchAllUsers();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/users', {
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

      await expect(fetchAllUsers()).rejects.toThrow(mockError);
    });
  });

  describe('overwritePassword', () => {
    it('should overwrite password and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Password overwritten' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const csrf = 'csrfToken';
      const userId = '1';
      const newPassword = 'newPassword';
      const passwordRepeat = 'newPassword';
      const result = await overwritePassword(csrf, userId, newPassword, passwordRepeat);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/overwrite-password', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrf,
          userId: userId,
          newPassword: newPassword,
          passwordRepeat: passwordRepeat,
        }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const csrf = 'csrfToken';
      const userId = '1';
      const newPassword = 'newPassword';
      const passwordRepeat = 'newPassword';

      await expect(overwritePassword(csrf, userId, newPassword, passwordRepeat)).rejects.toThrow(mockError);
    });
  });

  describe('postUserData', () => {
    it('should post user data and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'User data posted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const url = './api/user/data';
      const data = { name: 'User1' };
      const result = await postUserData(url, data);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(url, {
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

      const url = './api/user/data';
      const data = { name: 'User1' };

      await expect(postUserData(url, data)).rejects.toThrow(mockError);
    });
  });

  describe('deleteUser', () => {
    it('should delete user and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'User deleted' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const userId = '1';
      const csrfToken = 'csrfToken';
      const result = await deleteUser(userId, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/user/delete', {
        method: 'DELETE',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify({
          csrfToken: csrfToken,
          userId: userId,
        }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const userId = '1';
      const csrfToken = 'csrfToken';

      await expect(deleteUser(userId, csrfToken)).rejects.toThrow(mockError);
    });
  });
});
