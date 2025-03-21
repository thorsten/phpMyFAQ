import { describe, expect, test, vi } from 'vitest';
import { send } from './contact';
import { serialize } from '../utils';

describe('send function', () => {
  test('sends a message successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Contact message sent successfully' }),
    });

    // Test data
    const data = Object.keys({ text: 'contact message' }) as unknown as FormData;

    // Call the function
    const result = await send(data);

    // Assertions
    expect(result).toEqual({ success: 'Contact message sent successfully' });
    expect(fetch).toHaveBeenCalledWith('api/contact', {
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
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' }) as unknown as FormData;

    // Assertions
    await expect(send(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
