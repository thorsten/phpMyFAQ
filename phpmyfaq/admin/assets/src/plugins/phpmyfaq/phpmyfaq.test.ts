import { describe, it, expect, vi, beforeEach, afterEach, Mock } from 'vitest';

// Mock jodit before importing the plugin
const mockIconSet = vi.fn();
const mockPluginsAdd = vi.fn();

vi.mock('jodit', () => ({
  Jodit: {
    modules: {
      Icon: {
        set: mockIconSet,
      },
    },
    plugins: {
      add: mockPluginsAdd,
    },
  },
}));

vi.mock('../../api', () => ({
  fetchFaqsByAutocomplete: vi.fn(),
}));

import phpmyfaqSvg from './phpmyfaq.svg';
import { fetchFaqsByAutocomplete } from '../../api';

// Import the plugin to trigger its top-level side effects (Icon.set, plugins.add)
await import('./phpmyfaq.ts');

// Capture the plugin callback registered during top-level import
const pluginAddCall = mockPluginsAdd.mock.calls[0];
const capturedPluginCallback = pluginAddCall[1] as (editor: Record<string, unknown>) => void;

describe('phpmyfaq.svg', () => {
  it('should export a string containing SVG markup', () => {
    expect(typeof phpmyfaqSvg).toBe('string');
    expect(phpmyfaqSvg).toContain('<svg');
  });

  it('should contain SVG path elements', () => {
    expect(phpmyfaqSvg).toContain('<path');
  });
});

describe('phpMyFAQ Jodit Plugin', () => {
  let editor: Record<string, unknown>;
  let dialog: Record<string, unknown>;
  let consoleErrorSpy: ReturnType<typeof vi.spyOn>;
  let alertSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    document.body.innerHTML = '';

    dialog = {
      setMod: vi.fn().mockReturnThis(),
      setHeader: vi.fn().mockReturnThis(),
      setContent: vi.fn().mockReturnThis(),
      setSize: vi.fn().mockReturnThis(),
      open: vi.fn(),
      close: vi.fn(),
    };

    editor = {
      registerButton: vi.fn(),
      registerCommand: vi.fn(),
      dlg: vi.fn().mockReturnValue(dialog),
      selection: { insertHTML: vi.fn() },
      o: { theme: 'default' },
    };

    consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

    (fetchFaqsByAutocomplete as Mock).mockReset();
  });

  afterEach(() => {
    consoleErrorSpy.mockRestore();
    alertSpy.mockRestore();
  });

  it('should register the icon via Jodit.modules.Icon.set', () => {
    expect(mockIconSet).toHaveBeenCalledWith('phpmyfaq', expect.any(String));
  });

  it('should register the plugin via Jodit.plugins.add with name phpMyFAQ', () => {
    expect(mockPluginsAdd).toHaveBeenCalledWith('phpMyFAQ', expect.any(Function));
  });

  it('should register a button with correct config when plugin callback is invoked', () => {
    capturedPluginCallback(editor);

    expect(editor.registerButton).toHaveBeenCalledWith({
      name: 'phpMyFAQ',
      group: 'insert',
    });
  });

  it('should register a command named phpMyFAQ when plugin callback is invoked', () => {
    capturedPluginCallback(editor);

    expect(editor.registerCommand).toHaveBeenCalledWith('phpMyFAQ', expect.any(Function));
  });

  describe('command function', () => {
    let commandFn: () => void;

    beforeEach(() => {
      capturedPluginCallback(editor);

      const registerCommandCall = (editor.registerCommand as Mock).mock.calls[0];
      commandFn = registerCommandCall[1] as () => void;
    });

    const injectDialogHtml = () => {
      // The plugin calls dialog.setContent with the HTML, but our mock doesn't add it to DOM.
      // We manually inject it to test the event listeners.
      document.body.innerHTML = `
        <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
        <form class="row row-cols-lg-auto g-3 align-items-center m-4">
          <div class="col-12">
            <label class="visually-hidden" for="pmf-search-internal-links">Search</label>
            <input type="text" class="form-control" id="pmf-search-internal-links" placeholder="Search">
          </div>
        </form>
        <div class="m-4" id="pmf-search-results"></div>
        <div class="m-4">
          <button type="button" class="btn btn-primary" id="select-faq-button">Select FAQ</button>
        </div>
      `;
    };

    it('should create and open a dialog', () => {
      document.body.innerHTML = '<input type="hidden" id="pmf-csrf-token" value="test-csrf" />';

      const searchInput = document.createElement('input');
      searchInput.id = 'pmf-search-internal-links';
      document.body.appendChild(searchInput);

      const resultsContainer = document.createElement('div');
      resultsContainer.id = 'pmf-search-results';
      document.body.appendChild(resultsContainer);

      const selectButton = document.createElement('button');
      selectButton.id = 'select-faq-button';
      document.body.appendChild(selectButton);

      commandFn();

      expect(editor.dlg).toHaveBeenCalledWith({ closeOnClickOverlay: true });
      expect(dialog.setMod).toHaveBeenCalledWith('theme', 'default');
      expect(dialog.setHeader).toHaveBeenCalledWith('phpMyFAQ Plugin');
      expect(dialog.setContent).toHaveBeenCalledWith(expect.any(String));
      expect(dialog.setSize).toHaveBeenCalled();
      expect(dialog.open).toHaveBeenCalled();
    });

    it('should render radio buttons when search returns results', async () => {
      injectDialogHtml();

      (fetchFaqsByAutocomplete as Mock).mockResolvedValue({
        success: [{ url: '/faq/1', question: 'Test FAQ' }],
      });

      commandFn();

      const searchInput = document.getElementById('pmf-search-internal-links') as HTMLInputElement;
      searchInput.value = 'test';
      searchInput.dispatchEvent(new Event('keyup'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchFaqsByAutocomplete).toHaveBeenCalledWith('test', 'test-csrf-token');

      const resultsContainer = document.getElementById('pmf-search-results') as HTMLDivElement;
      expect(resultsContainer.innerHTML).toContain('Test FAQ');
      expect(resultsContainer.innerHTML).toContain('type="radio"');
      expect(resultsContainer.innerHTML).toContain('value="/faq/1"');
    });

    it('should clear results when search query is empty', async () => {
      injectDialogHtml();

      commandFn();

      // First set some content in the results container
      const resultsContainer = document.getElementById('pmf-search-results') as HTMLDivElement;
      resultsContainer.innerHTML = '<label>Previous result</label>';

      const searchInput = document.getElementById('pmf-search-internal-links') as HTMLInputElement;
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('keyup'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(resultsContainer.innerHTML).toBe('');
      expect(fetchFaqsByAutocomplete).not.toHaveBeenCalled();
    });

    it('should insert HTML link and close dialog when a radio is selected', () => {
      injectDialogHtml();

      commandFn();

      // Simulate radio buttons in the results
      const resultsContainer = document.getElementById('pmf-search-results') as HTMLDivElement;
      resultsContainer.innerHTML = `
        <label class="form-check-label">
          <input class="form-check-input" type="radio" name="faqURL" value="/faq/1" checked>
          Test FAQ
        </label><br>
      `;

      const selectButton = document.getElementById('select-faq-button') as HTMLButtonElement;
      selectButton.click();

      const insertHTMLFn = (editor.selection as Record<string, Mock>).insertHTML;
      expect(insertHTMLFn).toHaveBeenCalledWith(expect.stringContaining('/faq/1'));
      expect(insertHTMLFn).toHaveBeenCalledWith(expect.stringContaining('Test FAQ'));
      expect(insertHTMLFn).toHaveBeenCalledWith(expect.stringContaining('<a href='));
      expect(dialog.close).toHaveBeenCalled();
    });

    it('should show alert when no radio is selected', () => {
      injectDialogHtml();

      commandFn();

      const selectButton = document.getElementById('select-faq-button') as HTMLButtonElement;
      selectButton.click();

      expect(alertSpy).toHaveBeenCalledWith('Please select an FAQ.');
      expect(dialog.close).not.toHaveBeenCalled();
    });
  });
});
