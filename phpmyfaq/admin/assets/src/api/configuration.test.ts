import { describe, it, expect, vi, beforeEach } from 'vitest';
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
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchWrapper: vi.fn(),
  fetchJson: vi.fn(),
}));

describe('fetchConfiguration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch configuration data and return as text', async () => {
    const mockResponse = 'Configuration data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const target = '#main';
    const language = 'en';
    const result = await fetchConfiguration(target, language);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(`./api/configuration/list/main`, {
      headers: {
        'Accept-Language': language,
      },
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const target = '#main';
    const language = 'en';

    await expect(fetchConfiguration(target, language)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchFaqsSortingKeys', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch FAQs sorting keys and return as text', async () => {
    const mockResponse = 'Sorting keys data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingKeys(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(
      `./api/configuration/faqs-sorting-key/${currentValue}`
    );
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingKeys(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchFaqsSortingPopular', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch FAQs sorting popular data and return as text', async () => {
    const mockResponse = 'Popular sorting data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingPopular(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(
      `./api/configuration/faqs-sorting-popular/${currentValue}`
    );
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchFaqsSortingPopular(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchPermLevel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch permission level data and return as text', async () => {
    const mockResponse = 'Permission level data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchPermLevel(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(`./api/configuration/perm-level/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchPermLevel(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchReleaseEnvironment', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch release environment data and return as text', async () => {
    const mockResponse = 'Release environment data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchReleaseEnvironment(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(
      `./api/configuration/release-environment/${currentValue}`
    );
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchReleaseEnvironment(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchSearchRelevance', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch search relevance data and return as text', async () => {
    const mockResponse = 'Search relevance data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchSearchRelevance(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(
      `./api/configuration/search-relevance/${currentValue}`
    );
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchSearchRelevance(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchSeoMetaTags', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch SEO meta tags data and return as text', async () => {
    const mockResponse = 'SEO meta tags data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchSeoMetaTags(currentValue);

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith(`./api/configuration/seo-metatags/${currentValue}`);
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const currentValue = 'someValue';
    const result = await fetchSeoMetaTags(currentValue);

    expect(result).toBe('');
  });
});

describe('fetchTemplates', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch templates data and return as text', async () => {
    const mockResponse = 'Templates data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const result = await fetchTemplates();

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith('./api/configuration/templates');
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const result = await fetchTemplates();

    expect(result).toBe('');
  });
});

describe('fetchTranslations', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch translations data and return as text', async () => {
    const mockResponse = 'Translations data';
    const mockResponseObj = {
      ok: true,
      text: () => Promise.resolve(mockResponse),
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const result = await fetchTranslations();

    expect(result).toBe(mockResponse);
    expect(fetchWrapperModule.fetchWrapper).toHaveBeenCalledWith('./api/configuration/translations');
  });

  it('should return an empty string if the network response is not ok', async () => {
    const mockResponseObj = {
      ok: false,
    } as Response;
    vi.spyOn(fetchWrapperModule, 'fetchWrapper').mockResolvedValue(mockResponseObj);

    const result = await fetchTranslations();

    expect(result).toBe('');
  });
});

describe('saveConfiguration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should save configuration and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Configuration saved' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const formData = new FormData();
    formData.append('key', 'value');

    const result = await saveConfiguration(formData);

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('api/configuration', {
      method: 'POST',
      body: formData,
    });
  });
});
