import { describe, expect, test, vi } from 'vitest';
import { createQuestion } from './question';
import { serialize } from '../utils';

describe('createQuestion function', (): void => {
  test('creates a question successfully', async (): Promise<void> => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Question created successfully' }),
    });

    // Test data
    const data = Object.keys({ question: 'What is this all about?' }) as unknown as FormData;

    // Call the function
    const result = await createQuestion(data);

    // Assertions
    expect(result).toEqual({ success: 'Question created successfully' });
    expect(fetch).toHaveBeenCalledWith('api/question/create', {
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
    await expect(createQuestion(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
