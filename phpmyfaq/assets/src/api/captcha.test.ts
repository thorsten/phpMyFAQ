import { afterEach, beforeEach, describe, expect, test, vi, Mock } from 'vitest';
import { fetchCaptchaImage } from './captcha';

describe('fetchCaptchaImage function', () => {
  let fetchMock: Mock;

  beforeEach(() => {
    // Mocking fetch function
    fetchMock = vi.fn();
    global.fetch = fetchMock;
  });

  afterEach(() => {
    // Clear fetch mock
    fetchMock.mockClear();
  });

  test('should fetch captcha image successfully', async () => {
    const action: string = 'someAction';
    const timestamp: number = Date.now();
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
    expect(fetchMock).toHaveBeenCalledWith(`api/captcha?action=${action}&timestamp=${timestamp}`, {
      method: 'GET',
      cache: 'no-cache',
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    expect(response).toEqual(mockResponse);
  });

  test('should throw an error if network response is not ok', async () => {
    const action: string = 'someAction';
    const timestamp: number = Date.now();

    // Mock fetch function to return a non-ok response
    fetchMock.mockResolvedValueOnce({ ok: false, status: 500 });

    // Call the function and expect it to reject with an error
    await expect(fetchCaptchaImage(action, timestamp)).rejects.toThrow('HTTP 500');

    // Assertions
    expect(fetchMock).toHaveBeenCalledWith(`api/captcha?action=${action}&timestamp=${timestamp}`, {
      method: 'GET',
      cache: 'no-cache',
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });
});
