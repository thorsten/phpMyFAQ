import { fetchAutoCompleteData } from './autocomplete';
import createFetchMock from 'vitest-fetch-mock';

const fetchMocker = createFetchMock(vi);

fetchMocker.enableMocks();

describe('fetchAutoCompleteData', () => {
  beforeEach(() => {
    fetchMocker.resetMocks();
  });

  it('should return autocomplete data when the response is successful', async () => {
    const mockData = { suggestions: ['apple', 'banana', 'orange'] };

    fetch.mockResponseOnce(JSON.stringify(mockData));

    const searchString = 'fruit';
    const data = await fetchAutoCompleteData(searchString);

    expect(data).toEqual(mockData);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith(`api/autocomplete?search=${searchString}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error when the response is not successful', async () => {
    fetch.mockResponseOnce(null, { status: 500 });

    const searchString = 'fruit';

    await expect(fetchAutoCompleteData(searchString)).rejects.toThrow('Network response was not ok.');
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith(`api/autocomplete?search=${searchString}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should handle fetch error', async () => {
    // Simulating a network failure or fetch error
    fetch.mockRejectOnce(new Error('API is down'));

    const searchString = 'fruit';

    await expect(fetchAutoCompleteData(searchString)).rejects.toThrow('API is down');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
