import { beforeEach, describe, expect, test, vi } from 'vitest';
import { createBookmark, deleteBookmark, deleteAllBookmarks } from './bookmarks';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';
import { BookmarkResponse } from '../interfaces';

const fetchMocker: FetchMock = createFetchMock(vi);

fetchMocker.enableMocks();

describe('Bookmark API', (): void => {
  beforeEach((): void => {
    fetchMocker.resetMocks();
  });

  test('createBookmark should return response when successful', async (): Promise<void> => {
    const mockResponse = { success: 'Bookmark created' };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const faqId = '123';
    const csrf = 'csrfToken';
    const data: BookmarkResponse | undefined = await createBookmark(faqId, csrf);

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/bookmark/create', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify({ id: faqId, csrfToken: csrf }),
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('createBookmark should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    const faqId = '123';
    const csrf = 'csrfToken';

    await expect(createBookmark(faqId, csrf)).rejects.toThrow('HTTP 500');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('deleteBookmark should return response when successful', async (): Promise<void> => {
    const mockResponse = { success: 'Bookmark deleted' };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const faqId = '123';
    const csrf = 'csrfToken';
    const data: BookmarkResponse | undefined = await deleteBookmark(faqId, csrf);

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/bookmark/delete', {
      method: 'DELETE',
      cache: 'no-cache',
      body: JSON.stringify({ id: faqId, csrfToken: csrf }),
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('deleteBookmark should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    const faqId = '123';
    const csrf = 'csrfToken';

    await expect(deleteBookmark(faqId, csrf)).rejects.toThrow('HTTP 500');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('deleteAllBookmarks should return response when successful', async (): Promise<void> => {
    const mockResponse = { success: 'All bookmarks deleted' };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const csrf = 'csrfToken';
    const data: BookmarkResponse | undefined = await deleteAllBookmarks(csrf);

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/bookmark/delete-all', {
      method: 'DELETE',
      cache: 'no-cache',
      body: JSON.stringify({ csrfToken: csrf }),
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('deleteAllBookmarks should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    const csrf = 'csrfToken';

    await expect(deleteAllBookmarks(csrf)).rejects.toThrow('HTTP 500');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
