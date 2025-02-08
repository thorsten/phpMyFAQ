import { describe, it, expect, vi } from 'vitest';
import { fetchByLanguage, postStopWord, removeStopWord } from './stop-words';

global.fetch = vi.fn();

describe('Stop Words API', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('fetchByLanguage should fetch stop words by language', async () => {
    const mockResponse = [{ id: 1, lang: 'en', stopword: 'example' }];
    (fetch as vi.Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await fetchByLanguage('en');
    expect(fetch).toHaveBeenCalledWith('./api/stopwords?language=en', {
      method: 'GET',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
    expect(result).toEqual(mockResponse);
  });

  it('postStopWord should post a new stop word', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await postStopWord('csrfToken', 'example', 1, 'en');
    expect(fetch).toHaveBeenCalledWith('./api/stopword/save', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: 'csrfToken',
        stopWord: 'example',
        stopWordId: 1,
        stopWordsLang: 'en',
      }),
    });
    expect(result).toEqual(mockResponse);
  });

  it('removeStopWord should delete a stop word', async () => {
    const mockResponse = { success: true };
    (fetch as vi.Mock).mockResolvedValue({
      ok: true,
      json: async () => mockResponse,
    });

    const result = await removeStopWord('csrfToken', 1, 'en');
    expect(fetch).toHaveBeenCalledWith('./api/stopword/delete', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: 'csrfToken',
        stopWordId: 1,
        stopWordsLang: 'en',
      }),
    });
    expect(result).toEqual(mockResponse);
  });
});
