import { describe, it, expect, vi } from 'vitest';
import { deleteAttachments, refreshAttachments } from './attachment';

describe('deleteAttachments', () => {
  it('should delete attachment and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Attachment deleted' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const attachmentId = '123';
    const csrfToken = 'csrfToken';
    const result = await deleteAttachments(attachmentId, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/content/attachments', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ attId: attachmentId, csrf: csrfToken }),
    });
  });

  it('should throw an error if fetch fails', async () => {
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const attachmentId = '123';
    const csrfToken = 'csrfToken';

    await expect(deleteAttachments(attachmentId, csrfToken)).rejects.toThrow(mockError);
  });
});

describe('refreshAttachments', () => {
  it('should refresh attachment and return JSON response if successful', async () => {
    const mockResponse = { success: true, message: 'Attachment refreshed' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const attachmentId = '123';
    const csrfToken = 'csrfToken';
    const result = await refreshAttachments(attachmentId, csrfToken);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/content/attachments/refresh', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ attId: attachmentId, csrf: csrfToken }),
    });
  });

  it('should throw an error if fetch fails', async () => {
    const mockError = new Error('Fetch failed');
    global.fetch = vi.fn(() => Promise.reject(mockError));

    const attachmentId = '123';
    const csrfToken = 'csrfToken';

    await expect(refreshAttachments(attachmentId, csrfToken)).rejects.toThrow(mockError);
  });
});
