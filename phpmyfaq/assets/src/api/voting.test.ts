import { describe, expect, test, vi } from 'vitest';
import { saveVoting } from './voting';

describe('saveVoting', () => {
  test('saves a vote successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Vote saved successfully' }),
    });

    const result = await saveVoting('42', 'en', 4, 'test-csrf-token');

    expect(result).toEqual({ success: 'Vote saved successfully' });
    expect(fetch).toHaveBeenCalledWith('api/voting', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ id: '42', lang: 'en', value: 4, csrfToken: 'test-csrf-token' }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('returns error response if request fails', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    await expect(saveVoting('42', 'en', 3, 'test-csrf-token')).resolves.toEqual({ error: 'Something went wrong' });
  });

  test('returns undefined and logs error on network failure', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

    const result = await saveVoting('42', 'en', 5, 'test-csrf-token');

    expect(result).toBeUndefined();
    expect(consoleSpy).toHaveBeenCalled();

    consoleSpy.mockRestore();
  });
});
