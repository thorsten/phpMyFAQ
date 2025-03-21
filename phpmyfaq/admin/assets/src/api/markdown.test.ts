import { describe, it, expect, vi, Mock } from 'vitest';
import { fetchMarkdownContent } from './markdown';

describe('fetchMarkdownContent', () => {
  it('should fetch markdown content successfully', async () => {
    const mockResponse = {
      success: true,
      data: 'Mocked markdown content',
    };

    // Mock the fetch function
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })
    ) as Mock;

    const result = await fetchMarkdownContent('test markdown text');
    expect(result).toEqual(mockResponse);
  });

  it('should throw an error if the fetch fails', async () => {
    // Mock the fetch function to reject
    global.fetch = vi.fn((): Promise<never> => Promise.reject(new Error('Network error'))) as Mock;

    await expect(fetchMarkdownContent('test markdown text')).rejects.toThrow('Network error');
  });
});
