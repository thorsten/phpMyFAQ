import { describe, it, expect, vi, beforeEach } from 'vitest';
import { activateUser, deleteUser, overwritePassword, postUserData } from '../api';

global.fetch = vi.fn();

describe('User API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should overwrite password', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      json: vi.fn().mockResolvedValue(mockResponse),
    });

    const response = await overwritePassword('csrfToken', 'userId', 'newPassword', 'passwordRepeat');
    expect(fetch).toHaveBeenCalledWith('./api/user/overwrite-password', expect.any(Object));
    expect(response).toEqual(mockResponse);
  });

  it('should post user data', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      json: vi.fn().mockResolvedValue(mockResponse),
    });

    const response = await postUserData('url', { key: 'value' });
    expect(fetch).toHaveBeenCalledWith('url', expect.any(Object));
    expect(response).toEqual(mockResponse);
  });

  it('should activate user', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      json: vi.fn().mockResolvedValue(mockResponse),
    });

    const response = await activateUser('userId', 'csrfToken');
    expect(fetch).toHaveBeenCalledWith('./api/user/activate', expect.any(Object));
    expect(response).toEqual(mockResponse);
  });

  it('should delete user', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      json: vi.fn().mockResolvedValue(mockResponse),
    });

    const response = await deleteUser('userId', 'csrfToken');
    expect(fetch).toHaveBeenCalledWith('./api/user/delete', expect.any(Object));
    expect(response).toEqual(mockResponse);
  });
});
