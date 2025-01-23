import { describe, it, expect, vi } from 'vitest';
import { fetchCategoryTranslations, deleteCategory, setCategoryTree } from './category';

describe('fetchCategoryTranslations', () => {
  it('should fetch category translations and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Translations data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const categoryId = '123';
    const result = await fetchCategoryTranslations(categoryId);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/category/translations/${categoryId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if fetch fails', async () => {
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const categoryId = '123';

    await expect(fetchCategoryTranslations(categoryId)).rejects.toThrow(mockError);
  });
});

describe('deleteCategory', () => {
  it('should delete category and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Category deleted' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const categoryId = '123';
    const language = 'en';
    const csrfToken = 'csrfToken';
    const result = await deleteCategory(categoryId, language, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/category/delete', {
      method: 'DELETE',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        categoryId: categoryId,
        language: language,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if fetch fails', async () => {
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const categoryId = '123';
    const language = 'en';
    const csrfToken = 'csrfToken';

    await expect(deleteCategory(categoryId, language, csrfToken)).rejects.toThrow(mockError);
  });
});

describe('setCategoryTree', () => {
  it('should set category tree and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Category tree updated' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const categoryTree = { id: '123', name: 'Category' };
    const categoryId = '123';
    const csrfToken = 'csrfToken';
    const result = await setCategoryTree(categoryTree, categoryId, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/category/update-order', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        categoryTree: categoryTree,
        categoryId: categoryId,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if fetch fails', async () => {
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const categoryTree = { id: '123', name: 'Category' };
    const categoryId = '123';
    const csrfToken = 'csrfToken';

    await expect(setCategoryTree(categoryTree, categoryId, csrfToken)).rejects.toThrow(mockError);
  });
});
