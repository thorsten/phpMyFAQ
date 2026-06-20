import { beforeEach, describe, expect, test, vi } from 'vitest';
import { fetchPopularSearches } from './popularSearches';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';

const fetchMocker: FetchMock = createFetchMock(vi);
fetchMocker.enableMocks();

describe('fetchPopularSearches', (): void => {
  beforeEach((): void => {
    fetchMocker.resetMocks();
  });

  test('returns the parsed list on success and coerces the count to a number', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(JSON.stringify([{ id: 1, searchterm: 'mac', number: '18' }]));

    const data = await fetchPopularSearches();

    expect(data).toEqual([{ id: 1, searchterm: 'mac', number: 18 }]);
    expect(fetch).toHaveBeenCalledWith('api/searches/popular', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('drops malformed entries and keeps only well-formed ones', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(
      JSON.stringify([
        { id: 1, searchterm: 'mac', number: 18 },
        { id: 2, searchterm: '', number: 5 }, // empty searchterm
        { id: 3, number: 7 }, // missing searchterm
        { id: 4, searchterm: 'linux', number: 'not-a-number' }, // non-coercible
        { id: 5, searchterm: 'php', number: null }, // invalid number type
        'nonsense', // not an object
        { id: 6, searchterm: 'sqlite', number: '9' }, // valid, coerced
      ])
    );

    const data = await fetchPopularSearches();

    expect(data).toEqual([
      { id: 1, searchterm: 'mac', number: 18 },
      { id: 6, searchterm: 'sqlite', number: 9 },
    ]);
  });

  test('returns an empty array when the payload is not an array', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(JSON.stringify({ unexpected: 'shape' }));

    const data = await fetchPopularSearches();

    expect(data).toEqual([]);
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
