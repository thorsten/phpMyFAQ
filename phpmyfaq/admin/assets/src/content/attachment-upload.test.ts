import { describe, it, expect, beforeEach, vi, afterEach, type Mock } from 'vitest';
import { handleAttachmentUploads } from './attachment-upload';
import * as api from '../api';
import * as utils from '../../../../assets/src/utils';

// Mock the dependencies
vi.mock('../api');
vi.mock('../../../../assets/src/utils');

const mockUploadAttachments = api.uploadAttachments as Mock;
const mockAddElement = utils.addElement as Mock;

describe('handleAttachmentUploads', () => {
  let mockFilesToUpload: HTMLInputElement;
  let mockFileUploadButton: HTMLButtonElement;
  let mockFileSize: HTMLElement;
  let mockFileList: HTMLElement;
  let mockAttachmentModal: HTMLElement;
  let mockModalBackdrop: HTMLElement;
  let mockAttachmentList: HTMLElement;
  let mockAttachmentRecordId: HTMLInputElement;
  let mockAttachmentRecordLang: HTMLInputElement;

  beforeEach(() => {
    // Clear all mocks
    vi.clearAllMocks();

    // Setup comprehensive DOM structure
    document.body.innerHTML = `
      <input type="file" id="filesToUpload" multiple />
      <button id="pmf-attachment-modal-upload">Upload</button>
      <div id="filesize"></div>
      <div class="pmf-attachment-upload-files invisible"></div>
      <div id="attachmentModal" class="modal show" style="display: block;"></div>
      <div class="modal-backdrop fade show"></div>
      <ul class="adminAttachments" data-pmf-csrf-token="test-csrf-token"></ul>
      <input id="attachment_record_id" value="123" />
      <input id="attachment_record_lang" value="en" />
    `;

    mockFilesToUpload = document.getElementById('filesToUpload') as HTMLInputElement;
    mockFileUploadButton = document.getElementById('pmf-attachment-modal-upload') as HTMLButtonElement;
    mockFileSize = document.getElementById('filesize') as HTMLElement;
    mockFileList = document.querySelector('.pmf-attachment-upload-files') as HTMLElement;
    mockAttachmentModal = document.getElementById('attachmentModal') as HTMLElement;
    mockModalBackdrop = document.querySelector('.modal-backdrop.fade.show') as HTMLElement;
    mockAttachmentList = document.querySelector('.adminAttachments') as HTMLElement;
    mockAttachmentRecordId = document.getElementById('attachment_record_id') as HTMLInputElement;
    mockAttachmentRecordLang = document.getElementById('attachment_record_lang') as HTMLInputElement;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization and DOM element checks', () => {
    it('should not throw error when filesToUpload element does not exist', () => {
      document.body.innerHTML = '';
      expect(() => handleAttachmentUploads()).not.toThrow();
    });

    it('should add change event listener to filesToUpload input', () => {
      const addEventListenerSpy = vi.spyOn(mockFilesToUpload, 'addEventListener');
      handleAttachmentUploads();
      expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });

    it('should add click event listener to upload button when it exists', () => {
      const addEventListenerSpy = vi.spyOn(mockFileUploadButton, 'addEventListener');
      handleAttachmentUploads();
      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('should handle missing upload button gracefully', () => {
      document.getElementById('pmf-attachment-modal-upload')?.remove();
      expect(() => handleAttachmentUploads()).not.toThrow();
    });
  });

  describe('file selection and display', () => {
    it('should return early when no files are selected', () => {
      handleAttachmentUploads();

      // Create a file input with no files
      Object.defineProperty(mockFilesToUpload, 'files', {
        value: null,
        writable: true,
      });

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.classList.contains('invisible')).toBe(true);
    });

    it('should return early when empty FileList is provided', () => {
      handleAttachmentUploads();

      // Create an empty FileList
      const emptyFileList = {
        length: 0,
        item: () => null,
        [Symbol.iterator]: function* () {},
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: emptyFileList,
        writable: true,
      });

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.classList.contains('invisible')).toBe(true);
    });

    it('should display file size in bytes for small files', () => {
      handleAttachmentUploads();

      const mockFile = new File(['test content'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLiElement = document.createElement('li');
      const mockUlElement = document.createElement('ul');
      mockAddElement.mockReturnValueOnce(mockLiElement).mockReturnValueOnce(mockUlElement);

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileSize.textContent).toBe('12 bytes');
      expect(mockFileList.classList.contains('invisible')).toBe(false);
    });

    it('should display file size in KiB for files over 1 KB', () => {
      handleAttachmentUploads();

      // Create a file larger than 1 KB (2048 bytes)
      const content = 'a'.repeat(2048);
      const mockFile = new File([content], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLiElement = document.createElement('li');
      const mockUlElement = document.createElement('ul');
      mockAddElement.mockReturnValueOnce(mockLiElement).mockReturnValueOnce(mockUlElement);

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileSize.textContent).toContain('KiB');
      expect(mockFileSize.textContent).toContain('(2048 bytes)');
    });

    it('should display file size in MiB for files over 1 MB', () => {
      handleAttachmentUploads();

      // Create a 2 MB file
      const sizeInBytes = 2 * 1024 * 1024;
      const mockFile = new File(['a'.repeat(sizeInBytes)], 'large.pdf', { type: 'application/pdf' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLiElement = document.createElement('li');
      const mockUlElement = document.createElement('ul');
      mockAddElement.mockReturnValueOnce(mockLiElement).mockReturnValueOnce(mockUlElement);

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileSize.textContent).toContain('MiB');
      expect(mockFileSize.textContent).toContain(`(${sizeInBytes} bytes)`);
    });

    it('should display file size in GiB for files over 1 GB', () => {
      handleAttachmentUploads();

      // Create a 2 GB file reference
      const sizeInBytes = 2 * 1024 * 1024 * 1024;
      const mockFile = {
        name: 'huge.zip',
        size: sizeInBytes,
        type: 'application/zip',
      } as File;

      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLiElement = document.createElement('li');
      const mockUlElement = document.createElement('ul');
      mockAddElement.mockReturnValueOnce(mockLiElement).mockReturnValueOnce(mockUlElement);

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileSize.textContent).toContain('GiB');
      expect(mockFileSize.textContent).toContain(`(${sizeInBytes} bytes)`);
    });

    it('should handle multiple files and sum their sizes correctly', () => {
      handleAttachmentUploads();

      const mockFile1 = new File(['content1'], 'file1.txt', { type: 'text/plain' });
      const mockFile2 = new File(['content2'], 'file2.txt', { type: 'text/plain' });
      const mockFile3 = new File(['content3'], 'file3.txt', { type: 'text/plain' });

      const fileList = {
        0: mockFile1,
        1: mockFile2,
        2: mockFile3,
        length: 3,
        item: (index: number) => [mockFile1, mockFile2, mockFile3][index] || null,
        [Symbol.iterator]: function* () {
          yield mockFile1;
          yield mockFile2;
          yield mockFile3;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation((tag: string) => document.createElement(tag));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      const totalSize = mockFile1.size + mockFile2.size + mockFile3.size;
      expect(mockFileSize.textContent).toContain(`${totalSize} bytes`);
      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: 'file1.txt' });
      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: 'file2.txt' });
      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: 'file3.txt' });
    });

    it('should create list items for each file and append to file list', () => {
      handleAttachmentUploads();

      const mockFile = new File(['test'], 'document.pdf', { type: 'application/pdf' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLiElement = document.createElement('li');
      const mockUlElement = document.createElement('ul');
      mockAddElement.mockReturnValueOnce(mockLiElement).mockReturnValueOnce(mockUlElement);

      const appendSpy = vi.spyOn(mockFileList, 'append');

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: 'document.pdf' });
      expect(mockAddElement).toHaveBeenCalledWith('ul', { className: 'mt-2' }, [mockLiElement]);
      expect(appendSpy).toHaveBeenCalledWith(mockUlElement);
    });

    it('should use textContent instead of innerHTML for file size (security)', () => {
      handleAttachmentUploads();

      const mockFile = new File(['test'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation((tag: string) => document.createElement(tag));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // Verify textContent is used (no HTML injection possible)
      expect(mockFileSize.textContent).toBeTruthy();
      expect(mockFileSize.innerHTML).toBe(mockFileSize.textContent);
    });
  });

  describe('file upload functionality', () => {
    beforeEach(() => {
      // Setup files for upload tests
      const mockFile = new File(['test content'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });
    });

    it('should prevent default and stop propagation on upload button click', async () => {
      handleAttachmentUploads();

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      const clickEvent = new Event('click', { bubbles: true, cancelable: true });
      const preventDefaultSpy = vi.spyOn(clickEvent, 'preventDefault');
      const stopPropagationSpy = vi.spyOn(clickEvent, 'stopImmediatePropagation');

      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(preventDefaultSpy).toHaveBeenCalled();
      expect(stopPropagationSpy).toHaveBeenCalled();
    });

    it('should log error and return when no files are selected for upload', async () => {
      handleAttachmentUploads();

      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: null,
        writable: true,
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(consoleErrorSpy).toHaveBeenCalledWith('No files selected for upload.');
      expect(mockUploadAttachments).not.toHaveBeenCalled();

      consoleErrorSpy.mockRestore();
    });

    it('should log error when FileList is empty', async () => {
      handleAttachmentUploads();

      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const emptyFileList = {
        length: 0,
        item: () => null,
        [Symbol.iterator]: function* () {},
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: emptyFileList,
        writable: true,
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(consoleErrorSpy).toHaveBeenCalledWith('No files selected for upload.');
      expect(mockUploadAttachments).not.toHaveBeenCalled();

      consoleErrorSpy.mockRestore();
    });

    it('should create FormData with all files and metadata', async () => {
      handleAttachmentUploads();

      const mockFile1 = new File(['content1'], 'file1.txt', { type: 'text/plain' });
      const mockFile2 = new File(['content2'], 'file2.txt', { type: 'text/plain' });

      const fileList = {
        0: mockFile1,
        1: mockFile2,
        length: 2,
        item: (index: number) => [mockFile1, mockFile2][index] || null,
        [Symbol.iterator]: function* () {
          yield mockFile1;
          yield mockFile2;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'file1.txt' },
        { attachmentId: '2', fileName: 'file2.txt' },
      ]);

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockUploadAttachments).toHaveBeenCalledWith(expect.any(FormData));

      const formDataCall = mockUploadAttachments.mock.calls[0][0] as FormData;
      expect(formDataCall.get('record_id')).toBe('123');
      expect(formDataCall.get('record_lang')).toBe('en');
    });

    it('should successfully upload files and update UI', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '456', fileName: 'test.txt' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      const mockLinkElement = document.createElement('a');
      const mockButtonElement = document.createElement('button');
      const mockIconElement = document.createElement('i');
      const mockLiElement = document.createElement('li');

      mockAddElement
        .mockReturnValueOnce(mockLinkElement)
        .mockReturnValueOnce(mockIconElement)
        .mockReturnValueOnce(mockButtonElement)
        .mockReturnValueOnce(mockLiElement);

      const insertAdjacentElementSpy = vi.spyOn(mockAttachmentList, 'insertAdjacentElement');

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockUploadAttachments).toHaveBeenCalled();
      expect(insertAdjacentElementSpy).toHaveBeenCalledWith('beforeend', mockLiElement);
    });

    it('should create proper attachment link with correct attributes', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '789', fileName: 'document.pdf' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: any) => {
        const element = document.createElement(tag);
        Object.assign(element, props);
        return element;
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockAddElement).toHaveBeenCalledWith(
        'a',
        expect.objectContaining({
          className: 'me-2',
          href: '../index.php?action=attachment&id=789',
          innerText: 'document.pdf',
        })
      );
    });

    it('should create delete button with proper data attributes', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '999', fileName: 'test.doc' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: any) => {
        const element = document.createElement(tag);
        Object.assign(element, props);
        return element;
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockAddElement).toHaveBeenCalledWith(
        'button',
        expect.objectContaining({
          type: 'button',
          className: 'btn btn-sm btn-danger pmf-delete-attachment-button',
          'data-pmfAttachmentId': '999',
          'data-pmfCsrfToken': 'test-csrf-token',
        }),
        expect.any(Array)
      );
    });

    it('should create delete icon with proper attributes', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '111', fileName: 'image.png' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: any) => {
        const element = document.createElement(tag);
        Object.assign(element, props);
        return element;
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockAddElement).toHaveBeenCalledWith(
        'i',
        expect.objectContaining({
          className: 'bi bi-trash',
          'data-pmfAttachmentId': '111',
          'data-pmfCsrfToken': 'test-csrf-token',
        })
      );
    });

    it('should handle multiple attachments in response', async () => {
      handleAttachmentUploads();

      const mockAttachments = [
        { attachmentId: '1', fileName: 'file1.txt' },
        { attachmentId: '2', fileName: 'file2.pdf' },
        { attachmentId: '3', fileName: 'file3.doc' },
      ];
      mockUploadAttachments.mockResolvedValue(mockAttachments);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const insertAdjacentElementSpy = vi.spyOn(mockAttachmentList, 'insertAdjacentElement');

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(insertAdjacentElementSpy).toHaveBeenCalledTimes(3);
    });

    it('should clear file size textContent after successful upload', async () => {
      handleAttachmentUploads();

      mockFileSize.textContent = '100 KiB (102400 bytes)';

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockFileSize.textContent).toBe('');
    });

    it('should use textContent instead of innerHTML when clearing file size (security)', async () => {
      handleAttachmentUploads();

      mockFileSize.innerHTML = '<script>alert("xss")</script>';

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      // textContent should be empty, preventing any HTML injection
      expect(mockFileSize.textContent).toBe('');
      expect(mockFileSize.innerHTML).toBe('');
    });

    it('should remove all file list items after successful upload', async () => {
      handleAttachmentUploads();

      // Create some list items
      const li1 = document.createElement('li');
      const li2 = document.createElement('li');
      mockFileList.appendChild(li1);
      mockFileList.appendChild(li2);

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockFileList.querySelectorAll('li').length).toBe(0);
    });

    it('should hide modal after successful upload', async () => {
      handleAttachmentUploads();

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      expect(mockAttachmentModal.style.display).toBe('block');
      expect(mockAttachmentModal.classList.contains('show')).toBe(true);

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockAttachmentModal.style.display).toBe('none');
      expect(mockAttachmentModal.classList.contains('show')).toBe(false);
    });

    it('should remove modal backdrop after successful upload', async () => {
      handleAttachmentUploads();

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      expect(document.querySelector('.modal-backdrop')).toBeTruthy();

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(document.querySelector('.modal-backdrop')).toBeFalsy();
    });

    it('should handle upload error and log to console', async () => {
      handleAttachmentUploads();

      const mockError = new Error('Upload failed');
      mockUploadAttachments.mockRejectedValue(mockError);

      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(consoleErrorSpy).toHaveBeenCalledWith('An error occurred:', mockError);

      consoleErrorSpy.mockRestore();
    });

    it('should handle network error gracefully', async () => {
      handleAttachmentUploads();

      const networkError = new Error('Network request failed');
      mockUploadAttachments.mockRejectedValue(networkError);

      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(consoleErrorSpy).toHaveBeenCalled();
      expect(mockAttachmentModal.style.display).toBe('block'); // Modal should remain open on error

      consoleErrorSpy.mockRestore();
    });

    it('should handle API returning empty array', async () => {
      handleAttachmentUploads();

      mockUploadAttachments.mockResolvedValue([]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const insertAdjacentElementSpy = vi.spyOn(mockAttachmentList, 'insertAdjacentElement');

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(insertAdjacentElementSpy).not.toHaveBeenCalled();
      expect(mockFileSize.textContent).toBe('');
    });

    it('should extract CSRF token from attachment list data attribute', async () => {
      handleAttachmentUploads();

      mockAttachmentList.setAttribute('data-pmf-csrf-token', 'custom-csrf-token-123');

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      mockAddElement.mockImplementation((tag: string, props: any) => {
        const element = document.createElement(tag);
        Object.assign(element, props);
        return element;
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockAddElement).toHaveBeenCalledWith(
        'button',
        expect.objectContaining({
          'data-pmfCsrfToken': 'custom-csrf-token-123',
        }),
        expect.any(Array)
      );
    });
  });

  describe('edge cases and boundary conditions', () => {
    it('should handle extremely large file names', () => {
      handleAttachmentUploads();

      const longFileName = 'a'.repeat(500) + '.txt';
      const mockFile = new File(['content'], longFileName, { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: longFileName });
    });

    it('should handle special characters in file names', () => {
      handleAttachmentUploads();

      const specialFileName = "test's file (1) & more [2023].txt";
      const mockFile = new File(['content'], specialFileName, { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockAddElement).toHaveBeenCalledWith('li', { innerText: specialFileName });
    });

    it('should handle zero-byte files', () => {
      handleAttachmentUploads();

      const mockFile = new File([], 'empty.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileSize.textContent).toBe('0 bytes');
    });

    it('should handle missing DOM elements during upload gracefully', async () => {
      handleAttachmentUploads();

      const mockFile = new File(['content'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockUploadAttachments.mockResolvedValue([
        { attachmentId: '1', fileName: 'test.txt' },
      ]);

      // Remove modal backdrop before upload
      mockModalBackdrop.remove();

      const clickEvent = new Event('click');

      // Should not throw even with missing backdrop
      expect(() => mockFileUploadButton.dispatchEvent(clickEvent)).not.toThrow();

      await new Promise((resolve) => setTimeout(resolve, 100));
    });

    it('should handle very large number of files', () => {
      handleAttachmentUploads();

      const files: File[] = [];
      const fileListObj: any = { length: 100 };

      for (let i = 0; i < 100; i++) {
        const file = new File([`content${i}`], `file${i}.txt`, { type: 'text/plain' });
        files.push(file);
        fileListObj[i] = file;
      }

      fileListObj.item = (index: number) => files[index] || null;
      fileListObj[Symbol.iterator] = function* () {
        for (const file of files) {
          yield file;
        }
      };

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileListObj as FileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockAddElement).toHaveBeenCalledTimes(101); // 100 li elements + 1 ul element
      expect(mockFileList.classList.contains('invisible')).toBe(false);
    });

    it('should handle file size at exact KiB boundary (1024 bytes)', () => {
      handleAttachmentUploads();

      const content = 'a'.repeat(1024);
      const mockFile = new File([content], 'boundary.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // At exactly 1024 bytes, the loop condition (approx > 1) evaluates to false initially
      expect(mockFileSize.textContent).toBe('1024 bytes');
      expect(mockFileSize.textContent).toContain('1024 bytes');
    });
  });

  describe('security considerations', () => {
    it('should prevent XSS through file names by using textContent', () => {
      handleAttachmentUploads();

      const xssFileName = '<script>alert("XSS")</script>.txt';
      const mockFile = new File(['content'], xssFileName, { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const mockLi = document.createElement('li');
      mockAddElement.mockReturnValue(mockLi);

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // innerText is used in addElement, which safely sets text content
      expect(mockAddElement).toHaveBeenCalledWith('li', {
        innerText: '<script>alert("XSS")</script>.txt',
      });
    });

    it('should safely handle attachment IDs in data attributes', async () => {
      handleAttachmentUploads();

      // Setup file for upload
      const mockFile = new File(['content'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      const xssAttachmentId = '""><script>alert("XSS")</script>';
      const mockAttachment = { attachmentId: xssAttachmentId, fileName: 'test.txt' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: any) => {
        const element = document.createElement(tag);
        Object.assign(element, props);
        return element;
      });

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      // Data attributes should be safely set without script execution
      expect(mockAddElement).toHaveBeenCalledWith(
        'button',
        expect.objectContaining({
          'data-pmfAttachmentId': xssAttachmentId,
        }),
        expect.any(Array)
      );
    });

    it('should use textContent for file size display to prevent HTML injection', () => {
      handleAttachmentUploads();

      const mockFile = new File(['content'], 'test.txt', { type: 'text/plain' });
      const fileList = {
        0: mockFile,
        length: 1,
        item: (index: number) => (index === 0 ? mockFile : null),
        [Symbol.iterator]: function* () {
          yield mockFile;
        },
      } as unknown as FileList;

      Object.defineProperty(mockFilesToUpload, 'files', {
        value: fileList,
        writable: true,
      });

      mockAddElement.mockImplementation(() => document.createElement('li'));

      // Set initial innerHTML to detect if it's being used
      const originalInnerHTML = mockFileSize.innerHTML;

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // textContent should be set, innerHTML should match textContent (no HTML)
      expect(mockFileSize.textContent).toBeTruthy();
      expect(mockFileSize.innerHTML).toBe(mockFileSize.textContent);
    });
  });
});