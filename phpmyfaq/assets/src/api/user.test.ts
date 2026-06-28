import { describe, expect, test, vi } from 'vitest';
import {
  updateUserControlPanelData,
  updateUserPassword,
  resetUserPassword,
  requestUserRemoval,
  removeTwofactorConfig,
} from './user';
import { serialize } from '../utils';

describe('updateUserControlPanelData', () => {
  test('updates user data successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'User data updated successfully' }),
    });

    const data = Object.keys({ name: 'John Doe', email: 'john@example.com' }) as unknown as FormData;

    const result = await updateUserControlPanelData(data);

    expect(result).toEqual({ success: 'User data updated successfully' });
    expect(fetch).toHaveBeenCalledWith('api/user/data/update', {
      method: 'PUT',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('throws an error if the response is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    const data = Object.keys({ name: 'John Doe' }) as unknown as FormData;

    await expect(updateUserControlPanelData(data)).rejects.toThrow('HTTP 400');
  });
});

describe('updateUserPassword', () => {
  test('updates user password successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'Password updated successfully' }),
    });

    const data = Object.keys({ password: 'newPassword123' }) as unknown as FormData;

    const result = await updateUserPassword(data);

    expect(result).toEqual({ success: 'Password updated successfully' });
    expect(fetch).toHaveBeenCalledWith('api/user/password/update', {
      method: 'PUT',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('throws an error if the response is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    const data = Object.keys({ password: 'weak' }) as unknown as FormData;

    await expect(updateUserPassword(data)).rejects.toThrow('HTTP 400');
  });
});

describe('resetUserPassword', () => {
  test('resets user password successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'Password reset successfully' }),
    });

    const data = Object.keys({ email: 'john@example.com' }) as unknown as FormData;

    const result = await resetUserPassword(data);

    expect(result).toEqual({ success: 'Password reset successfully' });
    expect(fetch).toHaveBeenCalledWith('api/user/password/reset', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('throws an error if the response is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    const data = Object.keys({ email: 'invalid' }) as unknown as FormData;

    await expect(resetUserPassword(data)).rejects.toThrow('HTTP 400');
  });
});

describe('requestUserRemoval', () => {
  test('requests user removal successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'Removal request sent' }),
    });

    const data = Object.keys({ userId: '1', csrfToken: 'token123' }) as unknown as FormData;

    const result = await requestUserRemoval(data);

    expect(result).toEqual({ success: 'Removal request sent' });
    expect(fetch).toHaveBeenCalledWith('api/user/request-removal', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(serialize(data)),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('throws an error if the response is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    const data = Object.keys({ userId: '1' }) as unknown as FormData;

    await expect(requestUserRemoval(data)).rejects.toThrow('HTTP 400');
  });
});

describe('removeTwofactorConfig', () => {
  test('removes two-factor configuration successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'Two-factor authentication removed' }),
    });

    const result = await removeTwofactorConfig('csrf-token-123');

    expect(result).toEqual({ success: 'Two-factor authentication removed' });
    expect(fetch).toHaveBeenCalledWith('api/user/remove-twofactor', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ csrfToken: 'csrf-token-123' }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('throws an error if the response is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    await expect(removeTwofactorConfig('bad-token')).rejects.toThrow('HTTP 400');
  });
});
