import { describe, it, expect, beforeEach, vi, afterEach, type Mock } from 'vitest';
import { appendAttachmentToList, handleAttachmentDragAndDrop, handleAttachmentUploads } from './attachment-upload';
import * as api from '../api';
import * as utils from '../../../../assets/src/utils';

// Mock the dependencies
vi.mock('../api');
vi.mock('../../../../assets/src/utils');
vi.mock('./editor', () => ({
  getJoditEditor: vi.fn(() => null),
}));

const mockUploadAttachments = api.uploadAttachments as Mock;
const mockAddElement = utils.addElement as Mock;

const applyRealAddElementMock = (): void => {
  mockAddElement.mockImplementation((tag: string, props: Record<string, unknown> = {}, children: Node[] = []) => {
    const element = document.createElement(tag);
    Object.entries(props).forEach(([key, value]) => {
      if (key.startsWith('data-') || key.startsWith('aria-')) {
        element.setAttribute(key, value as string);
      } else if (key === 'innerText') {
        // jsdom does not reflect innerText into textContent, so map it here
        element.textContent = value as string;
      } else {
        (element as unknown as Record<string, unknown>)[key] = value;
      }
    });
    children.forEach((child) => element.appendChild(child));
    return element;
  });
};

describe('handleAttachmentUploads', () => {
  let mockFilesToUpload: HTMLInputElement;
  let mockFileUploadButton: HTMLButtonElement;
  let mockFileSize: HTMLElement;
  let mockFileList: HTMLElement;
  let mockAttachmentModal: HTMLElement;
  let mockModalBackdrop: HTMLElement;
  let mockAttachmentList: HTMLElement;

  beforeEach(() => {
    // Clear all mocks
    vi.clearAllMocks();

    // Setup comprehensive DOM structure
    document.body.innerHTML = `
      <input type="file" id="filesToUpload" data-pmf-custom-name-label="Custom filename (optional)" multiple />
      <button id="pmf-attachment-modal-upload">Upload</button>
      <div id="filesize"></div>
      <div class="pmf-attachment-upload-files invisible"></div>
      <div id="attachmentModal" class="modal show" style="display: block;"></div>
      <div class="modal-backdrop fade show"></div>
      <ul class="adminAttachments" data-pmf-csrf-token="test-csrf-token"></ul>
      <input id="attachment_record_id" value="123" />
      <input id="attachment_record_lang" value="en" />
      <input id="pmf-attachment-csrf-token" value="upload-token-xyz" />
    `;

    mockFilesToUpload = document.getElementById('filesToUpload') as HTMLInputElement;
    mockFileUploadButton = document.getElementById('pmf-attachment-modal-upload') as HTMLButtonElement;
    mockFileSize = document.getElementById('filesize') as HTMLElement;
    mockFileList = document.querySelector('.pmf-attachment-upload-files') as HTMLElement;
    mockAttachmentModal = document.getElementById('attachmentModal') as HTMLElement;
    mockModalBackdrop = document.querySelector('.modal-backdrop.fade.show') as HTMLElement;
    mockAttachmentList = document.querySelector('.adminAttachments') as HTMLElement;
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

      applyRealAddElementMock();

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

      applyRealAddElementMock();

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

      applyRealAddElementMock();

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

      applyRealAddElementMock();

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

      applyRealAddElementMock();

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      const totalSize = mockFile1.size + mockFile2.size + mockFile3.size;
      expect(mockFileSize.textContent).toContain(`${totalSize} bytes`);
      expect(mockFileList.textContent).toContain('file1.txt');
      expect(mockFileList.textContent).toContain('file2.txt');
      expect(mockFileList.textContent).toContain('file3.txt');
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

      applyRealAddElementMock();

      const appendSpy = vi.spyOn(mockFileList, 'append');

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.textContent).toContain('document.pdf');
      expect(mockAddElement).toHaveBeenCalledWith('ul', { className: 'list-unstyled mt-2' }, expect.any(Array));
      expect(appendSpy).toHaveBeenCalled();
    });

    it('should render a rename input per file with the original base name as placeholder', () => {
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

      applyRealAddElementMock();

      mockFilesToUpload.dispatchEvent(new Event('change'));

      const input = mockFileList.querySelector('input.pmf-attachment-custom-name') as HTMLInputElement;
      expect(input).not.toBeNull();
      expect(input.placeholder).toBe('document');
      expect(input.getAttribute('data-pmf-file-index')).toBe('0');
      expect(mockFileList.textContent).toContain('.pdf');
    });

    it('should render no extension suffix for a dotfile name', () => {
      handleAttachmentUploads();

      const mockFile = new File(['x'], '.gitignore', { type: 'text/plain' });
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

      applyRealAddElementMock();

      mockFilesToUpload.dispatchEvent(new Event('change'));

      const input = mockFileList.querySelector('input.pmf-attachment-custom-name') as HTMLInputElement;
      expect(input.placeholder).toBe('.gitignore');
      expect(mockFileList.querySelector('.input-group-text')).toBeNull();
    });

    it('should split only the last extension for multi-dot names', () => {
      handleAttachmentUploads();

      const mockFile = new File(['x'], 'archive.tar.gz', { type: 'application/gzip' });
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

      applyRealAddElementMock();

      mockFilesToUpload.dispatchEvent(new Event('change'));

      const input = mockFileList.querySelector('input.pmf-attachment-custom-name') as HTMLInputElement;
      expect(input.placeholder).toBe('archive.tar');
      expect((mockFileList.querySelector('.input-group-text') as HTMLElement).textContent).toBe('.gz');
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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

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
      expect(formDataCall.get('pmf-csrf-token')).toBe('upload-token-xyz');
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

      mockAddElement.mockImplementation((tag: string, props: Record<string, unknown>) => {
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
          href: '../attachment/789',
          innerText: 'document.pdf',
        })
      );
    });

    it('should create delete button with proper data attributes', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '999', fileName: 'test.doc' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: Record<string, unknown>) => {
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
          'data-pmf-attachment-id': '999',
          'data-pmf-csrf-token': 'test-csrf-token',
        }),
        expect.any(Array)
      );
    });

    it('should create delete icon with proper attributes', async () => {
      handleAttachmentUploads();

      const mockAttachment = { attachmentId: '111', fileName: 'image.png' };
      mockUploadAttachments.mockResolvedValue([mockAttachment]);

      mockAddElement.mockImplementation((tag: string, props: Record<string, unknown>) => {
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
          'data-pmf-attachment-id': '111',
          'data-pmf-csrf-token': 'test-csrf-token',
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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockFileSize.textContent).toBe('');
    });

    it('should use textContent instead of innerHTML when clearing file size (security)', async () => {
      handleAttachmentUploads();

      mockFileSize.innerHTML = '<script>alert("xss")</script>';

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

      mockAddElement.mockImplementation(() => document.createElement('div'));

      const clickEvent = new Event('click');
      mockFileUploadButton.dispatchEvent(clickEvent);

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(mockFileList.querySelectorAll('li').length).toBe(0);
    });

    it('should hide modal after successful upload', async () => {
      handleAttachmentUploads();

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

      mockAddElement.mockImplementation((tag: string, props: Record<string, unknown>) => {
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
          'data-pmf-csrf-token': 'custom-csrf-token-123',
        }),
        expect.any(Array)
      );
    });

    it('should append a customFileNames entry for every file, aligned by index', async () => {
      handleAttachmentUploads();

      const mockFile1 = new File(['content1'], 'file1.txt', { type: 'text/plain' });
      const mockFile2 = new File(['content2'], 'report.pdf', { type: 'application/pdf' });
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

      applyRealAddElementMock();

      mockFilesToUpload.dispatchEvent(new Event('change'));
      const inputs = mockFileList.querySelectorAll('input.pmf-attachment-custom-name');
      (inputs[1] as HTMLInputElement).value = '  my-invoice  ';

      mockUploadAttachments.mockResolvedValue([]);

      mockFileUploadButton.dispatchEvent(new Event('click'));
      await new Promise((resolve) => setTimeout(resolve, 100));

      const formData = mockUploadAttachments.mock.calls[0][0] as FormData;
      expect(formData.getAll('customFileNames[]')).toEqual(['', 'my-invoice']);
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

      applyRealAddElementMock();

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.textContent).toContain(longFileName);
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

      applyRealAddElementMock();

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.textContent).toContain(specialFileName);
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

      mockUploadAttachments.mockResolvedValue([{ attachmentId: '1', fileName: 'test.txt' }]);

      // Remove the modal backdrop before upload
      mockModalBackdrop.remove();

      const clickEvent = new Event('click');

      // Should not throw even with missing backdrop
      expect(() => mockFileUploadButton.dispatchEvent(clickEvent)).not.toThrow();

      await new Promise((resolve) => setTimeout(resolve, 100));
    });

    it('should handle very large number of files', () => {
      handleAttachmentUploads();

      const files: File[] = [];
      const fileListObj: Record<string, number | File | ((index: number) => File | null)> & {
        length: number;
        item: (index: number) => File | null;
        [Symbol.iterator]: () => Generator<File>;
      } = { length: 100, item: () => null, [Symbol.iterator]: function* () {} };

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

      applyRealAddElementMock();

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      expect(mockFileList.querySelectorAll('input.pmf-attachment-custom-name').length).toBe(100);
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

      applyRealAddElementMock();

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // The script must not become a live element; it is rendered as plain text only.
      expect(mockFileList.querySelector('script')).toBeNull();
      expect(mockFileList.textContent).toContain('<script>alert("XSS")</script>.txt');

      const innerHTMLCalls = mockAddElement.mock.calls.filter(([, props]) => props != null && 'innerHTML' in props);
      expect(innerHTMLCalls).toHaveLength(0);
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

      mockAddElement.mockImplementation((tag: string, props: Record<string, unknown>) => {
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
          'data-pmf-attachment-id': xssAttachmentId,
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

      const changeEvent = new Event('change');
      mockFilesToUpload.dispatchEvent(changeEvent);

      // textContent should be set, innerHTML should match textContent (no HTML)
      expect(mockFileSize.textContent).toBeTruthy();
      expect(mockFileSize.innerHTML).toBe(mockFileSize.textContent);
    });
  });
});

describe('appendAttachmentToList', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    applyRealAddElementMock();
  });

  it('should do nothing without an attachment list', () => {
    document.body.innerHTML = '';

    expect(() => appendAttachmentToList({ attachmentId: '1', fileName: 'test.txt' })).not.toThrow();
  });

  it('should append a list item with link and delete button', () => {
    document.body.innerHTML = '<ul class="adminAttachments" data-pmf-csrf-token="csrf-abc"></ul>';

    appendAttachmentToList({ attachmentId: '7', fileName: 'manual.pdf' });

    const item = document.getElementById('attachment-id-7') as HTMLElement;
    expect(item).not.toBeNull();
    expect((item.querySelector('a') as HTMLAnchorElement).textContent).toBe('manual.pdf');
    expect(
      (item.querySelector('button.pmf-delete-attachment-button') as HTMLElement).getAttribute('data-pmf-csrf-token')
    ).toBe('csrf-abc');
  });
});

describe('handleAttachmentDragAndDrop', () => {
  const mockUpload = api.uploadAttachments as Mock;

  const dropzoneMarkup = `
    <span id="pmf-attachment-count-badge" class="badge d-none">0</span>
    <div id="pmf-attachment-dropzone" data-pmf-max-size="1024" data-pmf-msg-too-big="Too big!">
      <button type="button" id="pmf-attachment-dropzone-browse">browse</button>
      <input type="file" id="pmf-attachment-dropzone-input" class="d-none" multiple>
    </div>
    <ul id="pmf-attachment-dropzone-progress"></ul>
    <ul class="adminAttachments" data-pmf-csrf-token="csrf-abc"></ul>
    <input id="attachment_record_id" value="123" />
    <input id="attachment_record_lang" value="en" />
    <input id="pmf-attachment-csrf-token" value="upload-token" />
  `;

  const dispatchDrop = (files: File[]): void => {
    const dropzone = document.getElementById('pmf-attachment-dropzone') as HTMLElement;
    const dropEvent = new Event('drop', { cancelable: true });
    Object.defineProperty(dropEvent, 'dataTransfer', { value: { files } });
    dropzone.dispatchEvent(dropEvent);
  };

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = dropzoneMarkup;
    applyRealAddElementMock();
  });

  it('should do nothing when the dropzone is missing', () => {
    document.body.innerHTML = '';

    expect(() => handleAttachmentDragAndDrop()).not.toThrow();
  });

  it('should toggle the dragover style on drag events', () => {
    handleAttachmentDragAndDrop();

    const dropzone = document.getElementById('pmf-attachment-dropzone') as HTMLElement;
    dropzone.dispatchEvent(new Event('dragover', { cancelable: true }));
    expect(dropzone.classList.contains('pmf-dragover')).toBe(true);

    dropzone.dispatchEvent(new Event('dragleave'));
    expect(dropzone.classList.contains('pmf-dragover')).toBe(false);
  });

  it('should upload dropped files, append them to the list, and update the badge', async () => {
    handleAttachmentDragAndDrop();

    mockUpload.mockResolvedValue([{ attachmentId: '11', fileName: 'dropped.txt' }]);

    dispatchDrop([new File(['content'], 'dropped.txt', { type: 'text/plain' })]);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mockUpload).toHaveBeenCalledTimes(1);
    const formData = mockUpload.mock.calls[0][0] as FormData;
    expect(formData.get('record_id')).toBe('123');
    expect(formData.get('record_lang')).toBe('en');
    expect(formData.get('pmf-csrf-token')).toBe('upload-token');

    expect(document.getElementById('attachment-id-11')).not.toBeNull();

    const badge = document.getElementById('pmf-attachment-count-badge') as HTMLElement;
    expect(badge.textContent).toBe('1');
    expect(badge.classList.contains('d-none')).toBe(false);

    const progressRow = document.querySelector('#pmf-attachment-dropzone-progress li') as HTMLElement;
    expect(progressRow.classList.contains('text-success')).toBe(true);
  });

  it('should skip files exceeding the maximum size', async () => {
    handleAttachmentDragAndDrop();

    dispatchDrop([new File(['a'.repeat(2048)], 'huge.bin', { type: 'application/octet-stream' })]);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mockUpload).not.toHaveBeenCalled();

    const progressRow = document.querySelector('#pmf-attachment-dropzone-progress li') as HTMLElement;
    expect(progressRow.classList.contains('text-danger')).toBe(true);
    expect(progressRow.textContent).toContain('Too big!');
  });

  it('should mark a failed upload and keep uploading remaining files', async () => {
    handleAttachmentDragAndDrop();

    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    mockUpload
      .mockRejectedValueOnce(new Error('Upload failed'))
      .mockResolvedValueOnce([{ attachmentId: '12', fileName: 'second.txt' }]);

    dispatchDrop([
      new File(['first'], 'first.txt', { type: 'text/plain' }),
      new File(['second'], 'second.txt', { type: 'text/plain' }),
    ]);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mockUpload).toHaveBeenCalledTimes(2);

    const progressRows = document.querySelectorAll<HTMLElement>('#pmf-attachment-dropzone-progress li');
    expect(progressRows[0].classList.contains('text-danger')).toBe(true);
    expect(progressRows[1].classList.contains('text-success')).toBe(true);
    expect(document.getElementById('attachment-id-12')).not.toBeNull();

    consoleErrorSpy.mockRestore();
  });

  it('should upload files chosen via the browse button', async () => {
    handleAttachmentDragAndDrop();

    mockUpload.mockResolvedValue([{ attachmentId: '13', fileName: 'browsed.txt' }]);

    const browseInput = document.getElementById('pmf-attachment-dropzone-input') as HTMLInputElement;
    const file = new File(['content'], 'browsed.txt', { type: 'text/plain' });
    Object.defineProperty(browseInput, 'files', {
      value: [file],
      configurable: true,
    });

    browseInput.dispatchEvent(new Event('change'));

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mockUpload).toHaveBeenCalledTimes(1);
    expect(document.getElementById('attachment-id-13')).not.toBeNull();
  });
});
