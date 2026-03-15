import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock matchMedia before any imports that might use it
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock MutationObserver
class MockMutationObserver {
  observe = vi.fn();
  disconnect = vi.fn();
  takeRecords = vi.fn();
}
global.MutationObserver = MockMutationObserver as unknown as typeof MutationObserver;

// Mock Jodit editor instance
const mockContainer = document.createElement('div');
mockContainer.classList.add('jodit_theme_default');

const mockEditorInstance = {
  options: { theme: 'default' },
  container: mockContainer,
  value: '',
};

vi.mock('jodit', () => ({
  Jodit: {
    make: vi.fn(() => mockEditorInstance),
  },
}));

vi.mock('jodit/esm/plugins/clean-html/clean-html.js', () => ({}));
vi.mock('jodit/esm/plugins/clipboard/clipboard.js', () => ({}));
vi.mock('jodit/esm/plugins/delete/delete.js', () => ({}));
vi.mock('jodit/esm/plugins/indent/indent.js', () => ({}));
vi.mock('jodit/esm/plugins/paste-from-word/paste-from-word.js', () => ({}));
vi.mock('jodit/esm/plugins/select/select.js', () => ({}));

import { renderCommentEditor } from './editor';
import { Jodit } from 'jodit';

describe('renderCommentEditor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    // Reset mock editor instance
    mockEditorInstance.options = { theme: 'default' };
    mockContainer.className = 'jodit_theme_default';
  });

  it('should return null when textarea element does not exist', () => {
    document.body.innerHTML = '<div></div>';

    const result = renderCommentEditor('#comment');

    expect(result).toBeNull();
    expect(Jodit.make).not.toHaveBeenCalled();
  });

  it('should return null for a non-matching selector', () => {
    document.body.innerHTML = '<textarea id="other"></textarea>';

    const result = renderCommentEditor('#comment');

    expect(result).toBeNull();
    expect(Jodit.make).not.toHaveBeenCalled();
  });

  it('should call Jodit.make when textarea element exists', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    const result = renderCommentEditor('#comment');

    expect(Jodit.make).toHaveBeenCalled();
    expect(result).toBe(mockEditorInstance);
  });

  it('should pass the textarea element to Jodit.make', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const element = callArgs[0] as HTMLTextAreaElement;
    expect(element.id).toBe('comment');
    expect(element.tagName).toBe('TEXTAREA');
  });

  it('should configure minimal toolbar buttons', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.buttons).toContain('bold');
    expect(config.buttons).toContain('italic');
    expect(config.buttons).toContain('underline');
    expect(config.buttons).toContain('strikethrough');
    expect(config.buttons).toContain('ul');
    expect(config.buttons).toContain('ol');
    expect(config.buttons).toContain('link');
    expect(config.buttons).toContain('undo');
    expect(config.buttons).toContain('redo');
  });

  it('should disable upload-related and advanced plugins', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.disablePlugins).toContain('image');
    expect(config.disablePlugins).toContain('video');
    expect(config.disablePlugins).toContain('file');
    expect(config.disablePlugins).toContain('media');
    expect(config.disablePlugins).toContain('table');
    expect(config.disablePlugins).toContain('iframe');
    expect(config.disablePlugins).toContain('drag-and-drop');
    expect(config.disablePlugins).toContain('fullsize');
    expect(config.disablePlugins).toContain('source');
  });

  it('should disable file uploaders', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.uploader.url).toBe('');
    expect(config.uploader.insertImageAsBase64URI).toBe(false);
    expect(config.filebrowser.ajax.url).toBe('');
  });

  it('should set height constraints', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.minHeight).toBe(200);
    expect(config.maxHeight).toBe(400);
  });

  it('should use default theme when prefers-color-scheme is light', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.theme).toBe('default');
  });

  it('should use dark theme when prefers-color-scheme is dark', () => {
    vi.mocked(window.matchMedia).mockImplementation((query: string) => ({
      matches: true,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }));

    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.theme).toBe('dark');
  });

  it('should apply theme from data-bs-theme attribute', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';
    document.documentElement.setAttribute('data-bs-theme', 'dark');

    renderCommentEditor('#comment');

    expect(mockEditorInstance.options.theme).toBe('dark');
    expect(mockContainer.classList.contains('jodit_theme_dark')).toBe(true);

    // Clean up
    document.documentElement.removeAttribute('data-bs-theme');
  });

  it('should set up MutationObserver for Bootstrap theme changes', () => {
    const observeCalls: unknown[][] = [];

    class SpyMutationObserver {
      observe = (...args: unknown[]) => {
        observeCalls.push(args);
      };
      disconnect = vi.fn();
      takeRecords = vi.fn();
    }
    global.MutationObserver = SpyMutationObserver as unknown as typeof MutationObserver;

    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    expect(observeCalls.length).toBe(1);
    expect(observeCalls[0][0]).toBe(document.documentElement);
    expect(observeCalls[0][1]).toEqual({ attributes: true, attributeFilter: ['data-bs-theme'] });

    // Restore original mock
    global.MutationObserver = MockMutationObserver as unknown as typeof MutationObserver;
  });

  it('should disable counters and statusbar extras', () => {
    document.body.innerHTML = '<textarea id="comment"></textarea>';

    renderCommentEditor('#comment');

    const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
    const config = callArgs[1];
    expect(config.showCharsCounter).toBe(false);
    expect(config.showWordsCounter).toBe(false);
    expect(config.showXPathInStatusbar).toBe(false);
  });
});
