import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleUploadCSVForm } from './csvimport';
import { pushNotification, pushErrorNotification } from '../../../../assets/src/utils';

vi.mock('../../../../assets/src/utils');

const setupBasicDom = (): void => {
  document.body.innerHTML = `
    <form id="uploadCSVFileForm" data-pmf-csrf="test-csrf-token">
      <input type="file" id="fileInputCSVUpload" />
      <button id="submitButton" type="submit">Upload</button>
    </form>
  `;
};

describe('CSV Import Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleUploadCSVForm', () => {
    it('should do nothing when submit button is missing', async () => {
      document.body.innerHTML = '<div></div>';

      await handleUploadCSVForm();

      // No event listeners should be attached
      expect(document.querySelectorAll('button').length).toBe(0);
    });

    it('should show error notification when no file is selected', async () => {
      setupBasicDom();

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('No file selected.');
    });

    it('should upload file and show success notification on OK response', async () => {
      setupBasicDom();

      const mockFile = new File(['question,answer\nQ1,A1'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      const mockFetch = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ success: 'Import successful' }),
      } as Response);

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(mockFetch).toHaveBeenCalledWith('./api/faq/import', expect.objectContaining({ method: 'POST' }));
      expect(pushNotification).toHaveBeenCalledWith('Import successful');
    });

    it('should clear file input after successful upload', async () => {
      setupBasicDom();

      const mockFile = new File(['data'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ success: 'Done' }),
      } as Response);

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fileInput.value).toBe('');
    });

    it('should show error notification on 400 response', async () => {
      setupBasicDom();

      const mockFile = new File(['bad data'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: false,
        status: 400,
        json: async () => ({ error: 'Invalid CSV format' }),
      } as Response);

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Invalid CSV format');
    });

    it('should show error notification on other error responses', async () => {
      setupBasicDom();

      const mockFile = new File(['data'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: false,
        status: 500,
        json: async () => ({ message: 'Internal server error' }),
      } as Response);

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(consoleSpy).toHaveBeenCalled();
      expect(pushErrorNotification).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });

    it('should handle import errors with messages array', async () => {
      setupBasicDom();

      const mockFile = new File(['data'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      vi.spyOn(globalThis, 'fetch').mockRejectedValue({
        storedAll: false,
        messages: ['Error on row 1', 'Error on row 2'],
      });

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Error on row 1');
      expect(pushErrorNotification).toHaveBeenCalledWith('Error on row 2');
    });

    it('should handle generic error during import', async () => {
      setupBasicDom();

      const mockFile = new File(['data'], 'faqs.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      vi.spyOn(globalThis, 'fetch').mockRejectedValue(new Error('Network failure'));

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(consoleSpy).toHaveBeenCalled();
      expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred during import');
      consoleSpy.mockRestore();
    });

    it('should send correct FormData with file and csrf token', async () => {
      setupBasicDom();

      const mockFile = new File(['content'], 'test.csv', { type: 'text/csv' });
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      Object.defineProperty(fileInput, 'files', {
        value: { 0: mockFile, length: 1, item: () => mockFile },
        writable: true,
      });

      const mockFetch = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ success: 'Done' }),
      } as Response);

      await handleUploadCSVForm();

      const button = document.getElementById('submitButton') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const formData = mockFetch.mock.calls[0][1]?.body as FormData;
      expect(formData.get('csrf')).toBe('test-csrf-token');
      expect(formData.get('file')).toBeTruthy();
    });
  });
});
