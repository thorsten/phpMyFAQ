import { describe, expect, test, vi } from 'vitest';
import { share } from './share';
import { serialize } from '../utils';

describe('share function', () => {
  test('shares a FAQ successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
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

  test('returns error response if request fails', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ faqId: '1' }) as unknown as FormData;

    // Assertions
    await expect(share(data)).resolves.toEqual({ error: 'Something went wrong' });
  });

  test('returns undefined and logs error on network failure', async () => {
    // Mocking fetch function to throw
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

    // Test data
    const data = Object.keys({ faqId: '1' }) as unknown as FormData;

    // Call the function
    const result = await share(data);

    // Assertions
    expect(result).toBeUndefined();
    expect(consoleSpy).toHaveBeenCalled();

    consoleSpy.mockRestore();
  });
});
