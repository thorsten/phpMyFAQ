import { beforeEach, describe, expect, test, vi } from 'vitest';
import { fetchTranslations } from './translations';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';

const fetchMocker: FetchMock = createFetchMock(vi);

fetchMocker.enableMocks();

describe('fetchTranslations', () => {
  beforeEach(() => {
    fetchMocker.resetMocks();
  });

  test('should return translations when the response is successful', async (): Promise<void> => {
    const mockTranslations = { hello: 'Hello', goodbye: 'Goodbye' };

    fetchMocker.mockResponseOnce(JSON.stringify(mockTranslations));

    const locale = 'en';
    const data: Record<string, string> = await fetchTranslations(locale);

    expect(data).toEqual(mockTranslations);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith(`/api/translations/${locale}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('should throw an error when the response is not successful', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    const locale = 'en';

    await expect(fetchTranslations(locale)).rejects.toThrow('Unexpected end of JSON input');
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith(`/api/translations/${locale}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('should handle fetch error', async (): Promise<void> => {
    fetchMocker.mockRejectOnce(new Error('API is down'));

    const locale = 'en';

    await expect(fetchTranslations(locale)).rejects.toThrow('API is down');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
