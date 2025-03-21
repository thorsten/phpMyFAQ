import { describe, expect, test, vi } from 'vitest';
import { register } from './register';
import { serialize } from '../utils';

describe('register function', () => {
  test('creates a registration successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Registration created successfully' }),
    });

    // Test data
    const data = Object.keys({ newUser: 'New User' }) as unknown as FormData;

    // Call the function
    const result = await register(data);

    // Assertions
    expect(result).toEqual({ success: 'Registration created successfully' });
    expect(fetch).toHaveBeenCalledWith('api/register', {
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
    await expect(register(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
