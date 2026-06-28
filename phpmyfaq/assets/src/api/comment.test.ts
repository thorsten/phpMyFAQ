import { describe, expect, test, vi } from 'vitest';
import { createComment } from './comment';
import { serialize } from '../utils';

describe('createComment function', () => {
  test('creates a comment successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ message: 'Comment created successfully' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' }) as unknown as FormData;

    // Call the function
    const result = await createComment(data);

    // Assertions
    expect(result).toEqual({ message: 'Comment created successfully' });
    expect(fetch).toHaveBeenCalledWith('api/comment/create', {
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
    const data = Object.keys({ comment: 'Test comment' }) as unknown as FormData;

    // Assertions
    await expect(createComment(data)).rejects.toThrow('HTTP 400');
  });
});
