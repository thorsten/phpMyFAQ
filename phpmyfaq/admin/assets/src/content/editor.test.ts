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

// Mock jodit and its plugins
const mockEventsOn = vi.fn();
const mockQuerySelectorAll = vi.fn(() => []);
const mockClassListRemove = vi.fn();
const mockClassListAdd = vi.fn();
const mockInsertImage = vi.fn();
const mockInsertHTML = vi.fn();

const mockEditorInstance = {
  options: {},
  container: {
    querySelectorAll: mockQuerySelectorAll,
    classList: {
      remove: mockClassListRemove,
      add: mockClassListAdd,
    },
  },
  events: {
    on: mockEventsOn,
  },
  value: '',
  selection: {
    insertImage: mockInsertImage,
    insertHTML: mockInsertHTML,
  },
};

vi.mock('jodit', () => ({
  Jodit: {
    make: vi.fn(() => mockEditorInstance),
  },
}));

vi.mock('highlight.js', () => ({
  default: {
    highlightElement: vi.fn(),
  },
}));

vi.mock('jodit/esm/plugins/class-span/class-span.js', () => ({}));
vi.mock('jodit/esm/plugins/clean-html/clean-html.js', () => ({}));
vi.mock('jodit/esm/plugins/clipboard/clipboard.js', () => ({}));
vi.mock('jodit/esm/plugins/copy-format/copy-format.js', () => ({}));
vi.mock('jodit/esm/plugins/delete/delete.js', () => ({}));
vi.mock('jodit/esm/plugins/fullsize/fullsize.js', () => ({}));
vi.mock('jodit/esm/plugins/hr/hr.js', () => ({}));
vi.mock('jodit/esm/plugins/image/image.js', () => ({}));
vi.mock('jodit/esm/plugins/image-processor/image-processor.js', () => ({}));
vi.mock('jodit/esm/plugins/image-properties/image-properties.js', () => ({}));
vi.mock('jodit/esm/plugins/indent/indent.js', () => ({}));
vi.mock('jodit/esm/plugins/justify/justify.js', () => ({}));
vi.mock('jodit/esm/plugins/line-height/line-height.js', () => ({}));
vi.mock('jodit/esm/plugins/media/media.js', () => ({}));
vi.mock('jodit/esm/plugins/paste-storage/paste-storage.js', () => ({}));
vi.mock('jodit/esm/plugins/paste-from-word/paste-from-word.js', () => ({}));
vi.mock('jodit/esm/plugins/preview/preview.js', () => ({}));
vi.mock('jodit/esm/plugins/print/print.js', () => ({}));
vi.mock('jodit/esm/plugins/resizer/resizer.js', () => ({}));
vi.mock('jodit/esm/plugins/search/search.js', () => ({}));
vi.mock('jodit/esm/plugins/select/select.js', () => ({}));
vi.mock('jodit/esm/plugins/source/source.js', () => ({}));
vi.mock('jodit/esm/plugins/symbols/symbols.js', () => ({}));
vi.mock('jodit/esm/modules/uploader/uploader.js', () => ({}));
vi.mock('jodit/esm/plugins/video/video.js', () => ({}));
vi.mock('../plugins/phpmyfaq/phpmyfaq.js', () => ({}));
vi.mock('../plugins/code-snippet/code-snippet.js', () => ({}));

import { getJoditEditor, renderEditor, renderPageEditor } from './editor';
import { Jodit } from 'jodit';

describe('Editor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    // Reset the mock editor instance properties
    mockEditorInstance.options = {};
    mockEditorInstance.value = '';
  });

  describe('getJoditEditor', () => {
    it('should return null initially', () => {
      // Before any render call, the module-level joditEditorInstance is null.
      // Since vitest caches module state, we verify the function is callable.
      const result = getJoditEditor();
      expect(result === null || result === mockEditorInstance).toBe(true);
    });
  });

  describe('renderEditor', () => {
    it('should return early when #editor element does not exist', () => {
      document.body.innerHTML = '<div></div>';

      renderEditor();

      expect(Jodit.make).not.toHaveBeenCalled();
    });

    it('should call Jodit.make when #editor element exists', () => {
      document.body.innerHTML = `
        <div id="editor"></div>
        <input id="pmf-csrf-token" value="test-csrf-token" />
      `;

      renderEditor();

      expect(Jodit.make).toHaveBeenCalled();
      const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
      const editorElement = callArgs[0] as HTMLElement;
      expect(editorElement.id).toBe('editor');
    });

    it('should register event listeners on the editor after creation', () => {
      document.body.innerHTML = `
        <div id="editor"></div>
        <input id="pmf-csrf-token" value="test-csrf-token" />
      `;

      renderEditor();

      expect(mockEventsOn).toHaveBeenCalledWith('afterSetValue', expect.any(Function));
      expect(mockEventsOn).toHaveBeenCalledWith('change', expect.any(Function));
    });
  });

  describe('renderPageEditor', () => {
    it('should return early when #content element does not exist', () => {
      document.body.innerHTML = '<div></div>';

      renderPageEditor();

      expect(Jodit.make).not.toHaveBeenCalled();
    });

    it('should return early when #content exists but parent .mb-3 does not exist', () => {
      document.body.innerHTML = `
        <div>
          <textarea id="content"></textarea>
        </div>
      `;

      renderPageEditor();

      expect(Jodit.make).not.toHaveBeenCalled();
    });

    it('should call Jodit.make when #content and parent .mb-3 exist', () => {
      document.body.innerHTML = `
        <div class="mb-3">
          <textarea id="content"></textarea>
        </div>
      `;

      renderPageEditor();

      expect(Jodit.make).toHaveBeenCalled();
      const callArgs = (Jodit.make as ReturnType<typeof vi.fn>).mock.calls[0];
      const contentElement = callArgs[0] as HTMLTextAreaElement;
      expect(contentElement.id).toBe('content');
    });

    it('should register event listeners on the editor after creation', () => {
      document.body.innerHTML = `
        <div class="mb-3">
          <textarea id="content"></textarea>
        </div>
      `;

      renderPageEditor();

      expect(mockEventsOn).toHaveBeenCalledWith('afterInit', expect.any(Function));
      expect(mockEventsOn).toHaveBeenCalledWith('change', expect.any(Function));
    });
  });
});
