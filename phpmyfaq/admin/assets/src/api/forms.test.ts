import { describe, it, expect, vi } from 'vitest';
import {
  fetchActivateInput,
  fetchSetInputAsRequired,
  fetchEditTranslation,
  fetchDeleteTranslation,
  fetchAddTranslation,
} from './forms';

describe('fetchActivateInput', () => {
  it('should activate input and return JSON response if successful', async () => {
    const mockResponse = { success: true };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const checked = true;
    const result = await fetchActivateInput(csrf, formId, inputId, checked);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/forms/activate', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formid: formId,
        inputid: inputId,
        checked: checked,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const checked = true;

    await expect(fetchActivateInput(csrf, formId, inputId, checked)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchSetInputAsRequired', () => {
  it('should set input as required and return JSON response if successful', async () => {
    const mockResponse = { success: true };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const checked = true;
    const result = await fetchSetInputAsRequired(csrf, formId, inputId, checked);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/forms/required', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formid: formId,
        inputid: inputId,
        checked: checked,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const checked = true;

    await expect(fetchSetInputAsRequired(csrf, formId, inputId, checked)).rejects.toThrow(
      'Network response was not ok.'
    );
  });
});

describe('fetchEditTranslation', () => {
  it('should edit translation and return JSON response if successful', async () => {
    const mockResponse = { success: true };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const label = 'label';
    const lang = 'en';
    const result = await fetchEditTranslation(csrf, formId, inputId, label, lang);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/forms/translation-edit', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
        label: label,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const label = 'label';
    const lang = 'en';

    await expect(fetchEditTranslation(csrf, formId, inputId, label, lang)).rejects.toThrow(
      'Network response was not ok.'
    );
  });
});

describe('fetchDeleteTranslation', () => {
  it('should delete translation and return JSON response if successful', async () => {
    const mockResponse = { success: true };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const lang = 'en';
    const result = await fetchDeleteTranslation(csrf, formId, inputId, lang);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/forms/translation-delete', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const lang = 'en';

    await expect(fetchDeleteTranslation(csrf, formId, inputId, lang)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchAddTranslation', () => {
  it('should add translation and return JSON response if successful', async () => {
    const mockResponse = { success: true };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const lang = 'en';
    const translation = 'translation';
    const result = await fetchAddTranslation(csrf, formId, inputId, lang, translation);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('api/forms/translation-add', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
        translation: translation,
      }),
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const csrf = 'csrfToken';
    const formId = 'formId';
    const inputId = 'inputId';
    const lang = 'en';
    const translation = 'translation';

    await expect(fetchAddTranslation(csrf, formId, inputId, lang, translation)).rejects.toThrow(
      'Network response was not ok.'
    );
  });
});
