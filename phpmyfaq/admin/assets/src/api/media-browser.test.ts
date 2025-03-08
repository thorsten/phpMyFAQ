import { describe, it, expect, vi, Mock } from 'vitest';
import { fetchMediaBrowserContent } from './media-browser';
import { MediaBrowserApiResponse } from '../interfaces';

describe('fetchMediaBrowserContent', () => {
  it('should fetch media browser content successfully', async (): Promise<void> => {
    const mockResponse: MediaBrowserApiResponse = {
      success: true,
      data: {
        sources: [
          {
            baseurl: 'http://example.com',
            path: 'images',
            files: [{ file: 'image1.jpg' }, { file: 'image2.jpg' }],
          },
        ],
      },
    };

    // Mock the fetch function
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: (): Promise<MediaBrowserApiResponse> => Promise.resolve(mockResponse),
      })
    ) as Mock;

    const result = (await fetchMediaBrowserContent()) as MediaBrowserApiResponse;
    expect(result).toEqual(mockResponse);
  });

  it('should throw an error if the fetch fails', async () => {
    // Mock the fetch function to reject
    global.fetch = vi.fn((): Promise<never> => Promise.reject(new Error('Network error'))) as Mock;

    await expect(fetchMediaBrowserContent()).rejects.toThrow('Network error');
  });
});
