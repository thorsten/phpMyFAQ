import { describe, it, expect, vi } from 'vitest';
import { createGlossary, deleteGlossary, getGlossary, updateGlossary } from './glossary';

describe('createGlossary', () => {
  it('should create a glossary item and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Glossary item created' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const language = 'en';
    const item = 'term';
    const definition = 'definition';
    const csrfToken = 'csrfToken';
    const result = await createGlossary(language, item, definition, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/glossary/create', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        language: language,
        item: item,
        definition: definition,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const language = 'en';
    const item = 'term';
    const definition = 'definition';
    const csrfToken = 'csrfToken';

    await expect(createGlossary(language, item, definition, csrfToken)).rejects.toThrow('Network response was not ok.');
  });
});

describe('deleteGlossary', () => {
  it('should delete a glossary item and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Glossary item deleted' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLang = 'en';
    const csrfToken = 'csrfToken';
    const result = await deleteGlossary(glossaryId, glossaryLang, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/glossary/delete', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        id: glossaryId,
        lang: glossaryLang,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLang = 'en';
    const csrfToken = 'csrfToken';

    await expect(deleteGlossary(glossaryId, glossaryLang, csrfToken)).rejects.toThrow('Network response was not ok.');
  });
});

describe('getGlossary', () => {
  it('should get a glossary item and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Glossary data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLanguage = 'en';
    const result = await getGlossary(glossaryId, glossaryLanguage);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/glossary/${glossaryId}/${glossaryLanguage}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLanguage = 'en';

    await expect(getGlossary(glossaryId, glossaryLanguage)).rejects.toThrow('Network response was not ok.');
  });
});

describe('updateGlossary', () => {
  it('should update a glossary item and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Glossary item updated' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLanguage = 'en';
    const item = 'term';
    const definition = 'definition';
    const csrfToken = 'csrfToken';
    const result = await updateGlossary(glossaryId, glossaryLanguage, item, definition, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/glossary/update', {
      method: 'PUT',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        id: glossaryId,
        lang: glossaryLanguage,
        item: item,
        definition: definition,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const glossaryId = '123';
    const glossaryLanguage = 'en';
    const item = 'term';
    const definition = 'definition';
    const csrfToken = 'csrfToken';

    await expect(updateGlossary(glossaryId, glossaryLanguage, item, definition, csrfToken)).rejects.toThrow(
      'Network response was not ok.'
    );
  });
});
