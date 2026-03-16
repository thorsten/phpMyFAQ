import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleMarkdownForm } from './markdown';
import { fetchMarkdownContent, fetchMediaBrowserContent } from '../api';
import { pushNotification, pushErrorNotification } from '../../../../assets/src/utils';

vi.mock('bootstrap', () => {
  const ModalMock = class {
    show = vi.fn();
    hide = vi.fn();
  };
  return { Modal: ModalMock };
});

vi.mock('../api', () => ({
  fetchMarkdownContent: vi.fn(),
  fetchMediaBrowserContent: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('handleMarkdownForm', () => {
  let mockLocalStorage: Record<string, string>;
  let consoleErrorSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    mockLocalStorage = {};
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation((key: string) => {
      return mockLocalStorage[key] ?? null;
    });
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation((key: string, value: string) => {
      mockLocalStorage[key] = value;
    });

    consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  it('should not throw when answer element does not exist', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleMarkdownForm()).not.toThrow();
    expect(consoleErrorSpy).not.toHaveBeenCalled();
  });

  it('should restore textarea height from localStorage', () => {
    mockLocalStorage['phpmyfaq.answer.height'] = '300px';

    document.body.innerHTML = '<textarea id="answer-markdown"></textarea>';

    handleMarkdownForm();

    const answer = document.getElementById('answer-markdown') as HTMLTextAreaElement;
    expect(answer.style.height).toBe('300px');
  });

  it('should save textarea height on mouseup', () => {
    document.body.innerHTML = '<textarea id="answer-markdown"></textarea>';

    handleMarkdownForm();

    const answer = document.getElementById('answer-markdown') as HTMLTextAreaElement;
    answer.style.height = '450px';
    answer.dispatchEvent(new Event('mouseup'));

    expect(localStorage.setItem).toHaveBeenCalledWith('phpmyfaq.answer.height', '450px');
  });

  it('should handle missing markdownTabs gracefully', () => {
    document.body.innerHTML = '<textarea id="answer-markdown"></textarea>';

    expect(() => handleMarkdownForm()).not.toThrow();
    expect(consoleErrorSpy).not.toHaveBeenCalled();
  });

  it('should handle missing insertImage gracefully', () => {
    document.body.innerHTML = `
      <textarea id="answer-markdown"></textarea>
      <div id="markdown-tabs"></div>
    `;

    expect(() => handleMarkdownForm()).not.toThrow();
    expect(consoleErrorSpy).not.toHaveBeenCalled();
  });

  it('should handle missing imageUpload gracefully', () => {
    document.body.innerHTML = `
      <textarea id="answer-markdown"></textarea>
      <div id="markdown-tabs"></div>
      <div id="pmf-markdown-insert-image"></div>
      <div id="pmf-markdown-insert-image-modal"></div>
      <div id="pmf-markdown-insert-image-button"></div>
    `;

    expect(() => handleMarkdownForm()).not.toThrow();
    expect(consoleErrorSpy).not.toHaveBeenCalled();
  });

  it('should fetch and render markdown preview on tab shown', async () => {
    (fetchMarkdownContent as ReturnType<typeof vi.fn>).mockResolvedValue({
      success: '<p>Rendered markdown</p>',
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown">## Hello</textarea>
      <div id="markdown-tabs">
        <a data-markdown-tab="preview">Preview</a>
      </div>
      <div id="markdown-preview"></div>
    `;

    handleMarkdownForm();

    const tab = document.querySelector('a[data-markdown-tab="preview"]') as HTMLElement;
    tab.dispatchEvent(new Event('shown.bs.tab'));

    await flushPromises();

    expect(fetchMarkdownContent).toHaveBeenCalledWith('## Hello');
    const preview = document.getElementById('markdown-preview') as HTMLElement;
    expect(preview.innerHTML).toBe('<p>Rendered markdown</p>');
  });

  it('should handle Error thrown during markdown preview fetch', async () => {
    (fetchMarkdownContent as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network error'));

    document.body.innerHTML = `
      <textarea id="answer-markdown">## Hello</textarea>
      <div id="markdown-tabs">
        <a data-markdown-tab="preview">Preview</a>
      </div>
      <div id="markdown-preview"></div>
    `;

    handleMarkdownForm();

    const tab = document.querySelector('a[data-markdown-tab="preview"]') as HTMLElement;
    tab.dispatchEvent(new Event('shown.bs.tab'));

    await flushPromises();

    expect(consoleErrorSpy).toHaveBeenCalledWith(expect.any(Error));
  });

  it('should handle non-Error thrown during markdown preview fetch', async () => {
    (fetchMarkdownContent as ReturnType<typeof vi.fn>).mockRejectedValue('string error');

    document.body.innerHTML = `
      <textarea id="answer-markdown">## Hello</textarea>
      <div id="markdown-tabs">
        <a data-markdown-tab="preview">Preview</a>
      </div>
      <div id="markdown-preview"></div>
    `;

    handleMarkdownForm();

    const tab = document.querySelector('a[data-markdown-tab="preview"]') as HTMLElement;
    tab.dispatchEvent(new Event('shown.bs.tab'));

    await flushPromises();

    expect(consoleErrorSpy).toHaveBeenCalledWith('Unknown error:', 'string error');
  });

  it('should show modal and populate image list when insert image is clicked', async () => {
    (fetchMediaBrowserContent as ReturnType<typeof vi.fn>).mockResolvedValue({
      success: true,
      data: {
        sources: [
          {
            baseurl: 'https://example.com',
            path: 'images',
            files: [
              { file: 'photo1.jpg', size: '100KB', isImage: true, thumb: '', changed: '' },
              { file: 'photo2.png', size: '200KB', isImage: true, thumb: '', changed: '' },
            ],
            name: 'source1',
          },
        ],
      },
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown"></textarea>
      <div id="pmf-markdown-insert-image"></div>
      <div id="pmf-markdown-insert-image-modal"></div>
      <button id="pmf-markdown-insert-image-button"></button>
      <div id="pmf-markdown-insert-image-list"></div>
    `;

    handleMarkdownForm();

    const insertImage = document.getElementById('pmf-markdown-insert-image') as HTMLElement;
    insertImage.click();

    await flushPromises();

    expect(fetchMediaBrowserContent).toHaveBeenCalled();
    const list = document.getElementById('pmf-markdown-insert-image-list') as HTMLElement;
    expect(list.querySelectorAll('.list-group-item').length).toBe(2);
    expect(list.innerHTML).toContain('photo1.jpg');
    expect(list.innerHTML).toContain('photo2.png');
  });

  it('should insert checked images as markdown at cursor position', async () => {
    (fetchMediaBrowserContent as ReturnType<typeof vi.fn>).mockResolvedValue({
      success: true,
      data: {
        sources: [
          {
            baseurl: 'https://example.com',
            path: 'images',
            files: [{ file: 'photo1.jpg', size: '100KB', isImage: true, thumb: '', changed: '' }],
            name: 'source1',
          },
        ],
      },
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown">Some text here</textarea>
      <div id="pmf-markdown-insert-image"></div>
      <div id="pmf-markdown-insert-image-modal"></div>
      <button id="pmf-markdown-insert-image-button"></button>
      <div id="pmf-markdown-insert-image-list"></div>
    `;

    handleMarkdownForm();

    const insertImage = document.getElementById('pmf-markdown-insert-image') as HTMLElement;
    insertImage.click();

    await flushPromises();

    // Check the checkbox
    const checkbox = document.querySelector('.form-check-input') as HTMLInputElement;
    checkbox.checked = true;

    const answer = document.getElementById('answer-markdown') as HTMLTextAreaElement;
    answer.selectionStart = 5;
    answer.selectionEnd = 5;

    const insertButton = document.getElementById('pmf-markdown-insert-image-button') as HTMLElement;
    insertButton.click();

    expect(answer.value).toContain('![Image](https://example.com/images/photo1.jpg)');
  });

  it('should handle image upload and insert uploaded images', async () => {
    const mockResponseData = {
      success: true,
      data: {
        sources: [
          {
            baseurl: 'https://example.com/',
            path: 'uploads/',
            files: ['uploaded.jpg'],
          },
        ],
      },
    };

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue(mockResponseData),
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown">Hello world</textarea>
      <div id="pmf-markdown-upload-image"></div>
      <input id="pmf-markdown-upload-image-input" type="file" />
      <input id="pmf-markdown-upload-image-csrf-token" value="csrf123" />
    `;

    handleMarkdownForm();

    const uploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLInputElement;
    const answer = document.getElementById('answer-markdown') as HTMLTextAreaElement;
    answer.selectionStart = 0;
    answer.selectionEnd = 0;

    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(uploadInput, 'files', { value: [file], writable: false });

    uploadInput.dispatchEvent(new Event('change'));

    await flushPromises();

    expect(global.fetch).toHaveBeenCalledWith('./api/content/images?csrf=csrf123', expect.any(Object));
    expect(pushNotification).toHaveBeenCalledWith('Files uploaded successfully');
    expect(answer.value).toContain('![Image](https://example.com/uploads/uploaded.jpg)');
  });

  it('should show error notification when upload response is not successful', async () => {
    const mockResponseData = {
      success: false,
      messages: 'File too large',
    };

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue(mockResponseData),
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown">Hello</textarea>
      <div id="pmf-markdown-upload-image"></div>
      <input id="pmf-markdown-upload-image-input" type="file" />
      <input id="pmf-markdown-upload-image-csrf-token" value="csrf123" />
    `;

    handleMarkdownForm();

    const uploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLInputElement;
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(uploadInput, 'files', { value: [file], writable: false });

    uploadInput.dispatchEvent(new Event('change'));

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Upload failed:File too large');
  });

  it('should show error notification when upload fetch throws', async () => {
    global.fetch = vi.fn().mockRejectedValue(new Error('Network failure'));

    document.body.innerHTML = `
      <textarea id="answer-markdown">Hello</textarea>
      <div id="pmf-markdown-upload-image"></div>
      <input id="pmf-markdown-upload-image-input" type="file" />
      <input id="pmf-markdown-upload-image-csrf-token" value="csrf123" />
    `;

    handleMarkdownForm();

    const uploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLInputElement;
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(uploadInput, 'files', { value: [file], writable: false });

    uploadInput.dispatchEvent(new Event('change'));

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith(expect.stringContaining('Error uploading files:'));
  });

  it('should show error notification when upload response.ok is false', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: vi.fn(),
    });

    document.body.innerHTML = `
      <textarea id="answer-markdown">Hello</textarea>
      <div id="pmf-markdown-upload-image"></div>
      <input id="pmf-markdown-upload-image-input" type="file" />
      <input id="pmf-markdown-upload-image-csrf-token" value="csrf123" />
    `;

    handleMarkdownForm();

    const uploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLInputElement;
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(uploadInput, 'files', { value: [file], writable: false });

    uploadInput.dispatchEvent(new Event('change'));

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith(expect.stringContaining('Error uploading files:'));
  });

  it('should trigger file input click when upload button is clicked', () => {
    document.body.innerHTML = `
      <textarea id="answer-markdown">Hello</textarea>
      <div id="pmf-markdown-upload-image"></div>
      <input id="pmf-markdown-upload-image-input" type="file" />
      <input id="pmf-markdown-upload-image-csrf-token" value="csrf123" />
    `;

    handleMarkdownForm();

    const uploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLInputElement;
    const clickSpy = vi.spyOn(uploadInput, 'click');

    const uploadButton = document.getElementById('pmf-markdown-upload-image') as HTMLElement;
    uploadButton.click();

    expect(clickSpy).toHaveBeenCalled();
  });
});
