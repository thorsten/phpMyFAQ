import { describe, expect, test, vi } from 'vitest';
import { saveVoting } from './voting';

describe('saveVoting', () => {
  test('saves a vote successfully', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
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

  test('throws an error if request is not ok', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    await expect(saveVoting('42', 'en', 3, 'test-csrf-token')).rejects.toThrow('HTTP 400');
  });

  test('throws on network failure', async () => {
    global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

    await expect(saveVoting('42', 'en', 5, 'test-csrf-token')).rejects.toThrow('Network error');
  });
});
