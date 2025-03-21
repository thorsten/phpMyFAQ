import { describe, expect, test, vi } from 'vitest';
import { createReport } from './export';

describe('createReport', () => {
  test('should create a report and return a Blob if successful', async () => {
    const mockBlob = new Blob(['Report data'], { type: 'application/pdf' });
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        blob: () => Promise.resolve(mockBlob),
      } as Response)
    );

    const data = { key: 'value' };
    const csrfToken = 'csrfToken';
    const result = await createReport(data, csrfToken);

    expect(result).toBe(mockBlob);
    expect(global.fetch).toHaveBeenCalledWith('./api/export/report', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data: data,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('should return JSON response if the network response is not ok', async () => {
    const mockResponse = { success: false, message: 'Error' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const data = { key: 'value' };
    const csrfToken = 'csrfToken';
    const result = await createReport(data, csrfToken);

    expect(result).toEqual(mockResponse);
  });

  test('should log an error if fetch fails', async () => {
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const data = { key: 'value' };
    const csrfToken = 'csrfToken';

    await createReport(data, csrfToken);

    expect(consoleErrorSpy).toHaveBeenCalledWith(mockError);
    consoleErrorSpy.mockRestore();
  });
});
