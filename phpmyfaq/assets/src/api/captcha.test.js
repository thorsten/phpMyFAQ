import { fetchCaptchaImage } from './captcha';

describe('fetchCaptchaImage function', () => {
  let fetchMock;
  let consoleErrorMock;

  beforeEach(() => {
    // Mocking fetch function
    fetchMock = vi.fn();
    global.fetch = fetchMock;

    // Mocking console.error
    consoleErrorMock = vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    // Clear fetch mock
    fetchMock.mockClear();

    // Restore console.error mock
    consoleErrorMock.mockRestore();
  });

  it('should fetch captcha image successfully', async () => {
    const action = 'someAction';
    const timestamp = Date.now();
    const requestBody = {
      action,
      timestamp,
    };
    const mockResponse = {
      ok: true,
      json: async () => ({
        /* mock response data */
      }),
    };

    // Mock fetch function to return a resolved Promise with a mockResponse
    fetchMock.mockResolvedValueOnce(mockResponse);

    // Call the function
    const response = await fetchCaptchaImage(action, timestamp);

    // Assertions
    expect(fetchMock).toHaveBeenCalledWith('api/captcha', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify(requestBody),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    expect(response).toEqual(mockResponse);

    // Verify console.error was not called
    expect(consoleErrorMock).not.toHaveBeenCalled();
  });

  it('should handle fetch error', async () => {
    const action = 'someAction';
    const timestamp = Date.now();
    const requestBody = {
      action,
      timestamp,
    };
    const errorMessage = 'Network error';

    // Mock fetch function to return a rejected Promise
    fetchMock.mockRejectedValueOnce(new Error(errorMessage));

    // Call the function and expect it to reject with an error
    await expect(fetchCaptchaImage(action, timestamp)).resolves.toEqual(undefined);

    // Assertions
    expect(fetchMock).toHaveBeenCalledWith('api/captcha', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify(requestBody),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    // Verify console.error was called with the expected message
    expect(consoleErrorMock).toHaveBeenCalledWith(new Error(errorMessage));
  });
});
