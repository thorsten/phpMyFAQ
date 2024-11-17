import { createQuestion } from './question';
import { serialize } from '../utils';

describe('createQuestion function', () => {
  it('creates a question successfully', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Question created successfully' }),
    });

    // Test data
    const data = Object.keys({ question: 'What is this all about?' });

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

  it('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = vi.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' });

    // Assertions
    await expect(createQuestion(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
