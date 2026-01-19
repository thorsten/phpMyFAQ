import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchByLanguage, postStopWord, removeStopWord } from './stop-words';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

describe('Stop Words API', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('fetchByLanguage should fetch stop words by language', async () => {
    const mockResponse = [{ id: 1, lang: 'en', stopword: 'example' }];
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const result = await fetchByLanguage('en');
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/stopwords?language=en', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });
    expect(result).toEqual(mockResponse);
  });

  it('postStopWord should post a new stop word', async () => {
    const mockResponse = { success: true };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const result = await postStopWord('csrfToken', 'example', 1, 'en');
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/stopword/save', {
      method: 'POST',
      headers: {
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
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const result = await removeStopWord('csrfToken', 1, 'en');
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/stopword/delete', {
      method: 'POST',
      headers: {
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
