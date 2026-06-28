import { describe, expect, test, vi } from 'vitest';
import { share } from './share';
import { serialize } from '../utils';

describe('share function', () => {
  test('shares a FAQ successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: 'FAQ shared successfully' }),
    });

    // Test data
    const data = Object.keys({ faqId: '1', email: 'test@example.com' }) as unknown as FormData;

    // Call the function
    const result = await share(data);

    // Assertions
    expect(result).toEqual({ success: 'FAQ shared successfully' });
    expect(fetch).toHaveBeenCalledWith('api/share', {
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

  test('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ faqId: '1' }) as unknown as FormData;

    // Assertions
    await expect(share(data)).rejects.toThrow('HTTP 400');
  });
});
