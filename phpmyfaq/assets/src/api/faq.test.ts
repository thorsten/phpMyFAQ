import { describe, expect, test, vi } from 'vitest';
import { createFaq } from './faq';
import { serialize } from '../utils';

describe('createFaq function', () => {
  test('creates a FAQ successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'FAQ created successfully' }),
    });

    // Test data
    const data = Object.keys({ question: 'What is this all about?' }) as unknown as FormData;

    // Call the function
    const result = await createFaq(data);

    // Assertions
    expect(result).toEqual({ success: 'FAQ created successfully' });
    expect(fetch).toHaveBeenCalledWith('api/faq/create', {
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
    await expect(createFaq(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
