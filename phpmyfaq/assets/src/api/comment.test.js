import { createComment } from './comment';
import { serialize } from '../utils';

describe('createComment function', () => {
  it('creates a comment successfully', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ message: 'Comment created successfully' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' });

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

  it('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' });

    // Assertions
    await expect(createComment(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
