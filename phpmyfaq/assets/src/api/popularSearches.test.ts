import { beforeEach, describe, expect, test, vi } from 'vitest';
import { fetchPopularSearches } from './popularSearches';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';

const fetchMocker: FetchMock = createFetchMock(vi);
fetchMocker.enableMocks();

describe('fetchPopularSearches', (): void => {
  beforeEach((): void => {
    fetchMocker.resetMocks();
  });

  test('returns the parsed list on success', async (): Promise<void> => {
    const mockData = [{ id: 1, searchterm: 'mac', number: '18' }];
    fetchMocker.mockResponseOnce(JSON.stringify(mockData));

    const data = await fetchPopularSearches();

    expect(data).toEqual(mockData);
    expect(fetch).toHaveBeenCalledWith('api/searches/popular', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('returns an empty array on a non-ok response', async (): Promise<void> => {
    fetchMocker.mockResponseOnce('[]', { status: 404 });

    const data = await fetchPopularSearches();

    expect(data).toEqual([]);
  });

  test('returns an empty array when fetch rejects', async (): Promise<void> => {
    fetchMocker.mockRejectOnce(new Error('API is down'));

    const data = await fetchPopularSearches();

    expect(data).toEqual([]);
  });
});
