import { describe, expect, vi, test } from 'vitest';
import { fetchAllFaqsByCategory, fetchFaqsByAutocomplete, deleteFaq, create, update } from './faqs';

describe('fetchAllFaqsByCategory', () => {
  test('should fetch all FAQs by category and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'FAQs data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const categoryId = '123';
    const language = 'en';
    const result = await fetchAllFaqsByCategory(categoryId, language);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalled();
  });
});

describe('fetchFaqsByAutocomplete', () => {
  test('should fetch FAQs by autocomplete and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Autocomplete data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const searchTerm = 'faq';
    const csrfToken = 'csrfToken';
    const result = await fetchFaqsByAutocomplete(searchTerm, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalled();
  });

  test('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const searchTerm = 'faq';
    const csrfToken = 'csrfToken';

    await expect(fetchFaqsByAutocomplete(searchTerm, csrfToken)).rejects.toThrow('Network response was not ok.');
  });
});

describe('deleteFaq', () => {
  test('should delete FAQ and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'FAQ deleted' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const faqId = '123';
    const faqLanguage = 'en';
    const csrfToken = 'csrfToken';
    const result = await deleteFaq(faqId, faqLanguage, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalled();
  });

  test('should throw an error if the network response is not ok', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const faqId = '123';
    const faqLanguage = 'en';
    const csrfToken = 'csrfToken';

    await expect(deleteFaq(faqId, faqLanguage, csrfToken)).rejects.toThrow('Network response was not ok.');
  });
});

describe('create', () => {
  test('should create a FAQ and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'FAQ created' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const formData = { key: 'value' };
    const result = await create(formData);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalled();
  });
});

describe('update', () => {
  test('should update a FAQ and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'FAQ updated' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const formData = { key: 'value' };
    const result = await update(formData);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalled();
  });
});
