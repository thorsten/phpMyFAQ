import { fetchAutoCompleteData } from './autocomplete';

describe('fetchAutoCompleteData function', () => {
  it('fetches autocomplete data successfully', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 200,
      json: () => Promise.resolve({ data: 'autocomplete data' }),
    });

    // Call the function
    const result = await fetchAutoCompleteData('searchString');

    // Assertions
    expect(result).toEqual({ data: 'autocomplete data' });
    expect(fetch).toHaveBeenCalledWith('api/autocomplete?search=searchString', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('throws an error if network response is not ok', async () => {
    // Mocking fetch function
    global.fetch = jest.fn().mockResolvedValue({
      status: 404,
    });

    // Assertions
    await expect(fetchAutoCompleteData('searchString')).rejects.toThrow('Network response was not ok.');
  });
});
