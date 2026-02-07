import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleMarkdownForm } from './markdown';

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
});
