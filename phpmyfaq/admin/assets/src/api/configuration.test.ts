import { describe, it, expect, vi } from 'vitest';
import {
  fetchConfiguration,
  fetchFaqsSortingKeys,
  fetchFaqsSortingPopular,
  fetchPermLevel,
  fetchReleaseEnvironment,
  fetchSearchRelevance,
  fetchSeoMetaTags,
  fetchTemplates,
  fetchTranslations,
  saveConfiguration,
} from './configuration';

describe('fetchConfiguration', () => {
  it('should fetch configuration data and return as text', async () => {
    const mockResponse = 'Configuration data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const target = '#main';
    const language = 'en';
    const result = await fetchConfiguration(target, language);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/list/main`, {
      headers: {
        'Accept-Language': language,
      },
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const target = '#main';
    const language = 'en';

    await expect(fetchConfiguration(target, language)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchFaqsSortingKeys', () => {
  it('should fetch FAQs sorting keys and return as text', async () => {
    const mockResponse = 'Sorting keys data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingKeys(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/faqs-sorting-key/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingKeys(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchFaqsSortingPopular', () => {
  it('should fetch FAQs sorting popular data and return as text', async () => {
    const mockResponse = 'Popular sorting data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingPopular(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/faqs-sorting-popular/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingPopular(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchPermLevel', () => {
  it('should fetch permission level data and return as text', async () => {
    const mockResponse = 'Permission level data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchPermLevel(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/perm-level/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchPermLevel(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchReleaseEnvironment', () => {
  it('should fetch release environment data and return as text', async () => {
    const mockResponse = 'Release environment data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchReleaseEnvironment(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/release-environment/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchReleaseEnvironment(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchSearchRelevance', () => {
  it('should fetch search relevance data and return as text', async () => {
    const mockResponse = 'Search relevance data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchSearchRelevance(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/search-relevance/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchSearchRelevance(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchSeoMetaTags', () => {
  it('should fetch SEO meta tags data and return as text', async () => {
    const mockResponse = 'SEO meta tags data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchSeoMetaTags(currentValue);

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/configuration/seo-metatags/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const currentValue = 'someValue';
    const result = await fetchSeoMetaTags(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchTemplates', () => {
  it('should fetch templates data and return as text', async () => {
    const mockResponse = 'Templates data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const result = await fetchTemplates();

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/configuration/templates');
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const result = await fetchTemplates();

    expect(result).toBe('');
  });
});

describe('fetchTranslations', () => {
  it('should fetch translations data and return as text', async () => {
    const mockResponse = 'Translations data';
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        text: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const result = await fetchTranslations();

    expect(result).toBe(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/configuration/translations');
  });

  it('should return an empty string if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: false,
      } as Response)
    );

    const result = await fetchTranslations();

    expect(result).toBe('');
  });
});

describe('saveConfiguration', () => {
  it('should save configuration and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Configuration saved' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        success: true,
        json: () => Promise.resolve(mockResponse),
      } as unknown as Response)
    );

    const formData = new FormData();
    formData.append('key', 'value');

    const result = await saveConfiguration(formData);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/configuration', {
      method: 'POST',
      body: formData,
    });
  });
});
