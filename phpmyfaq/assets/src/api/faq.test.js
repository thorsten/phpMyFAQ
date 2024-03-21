import { createFaq } from './faq';
import { serialize } from '../utils';

describe('createFaq function', () => {
  it('creates a FAQ successfully', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'FAQ created successfully' }),
    });

    // Test data
    const data = Object.keys({ question: 'What is this all about?' });

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

  it('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' });

    // Assertions
    await expect(createFaq(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
