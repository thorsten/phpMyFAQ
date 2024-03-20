import { send } from './contact';
import { serialize } from '../utils';

describe('send function', () => {
  it('sends a message successfully', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ success: 'Contact message sent successfully' }),
    });

    // Test data
    const data = Object.keys({ text: 'contact message' });

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

  it('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 400,
      json: () => Promise.resolve({ error: 'Something went wrong' }),
    });

    // Test data
    const data = Object.keys({ comment: 'Test comment' });

    // Assertions
    await expect(send(data)).resolves.toEqual({ error: 'Something went wrong' });
  });
});
