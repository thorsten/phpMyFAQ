import { describe, it, expect, vi, beforeEach } from 'vitest';

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

describe('code-snippet.svg', () => {
  it('should export a string containing SVG markup', async () => {
    const { default: svgContent } = await import('./code-snippet.svg.js');
    expect(typeof svgContent).toBe('string');
    expect(svgContent).toContain('<svg');
  });

  it('should contain SVG path elements', async () => {
    const { default: svgContent } = await import('./code-snippet.svg.js');
    expect(svgContent).toContain('<path');
  });
});

describe('code-snippet plugin', () => {
  const mockDialog = {
    setMod: vi.fn(),
    setHeader: vi.fn(),
    setContent: vi.fn(),
    setSize: vi.fn(),
    open: vi.fn(),
    close: vi.fn(),
  };

  const mockEditor = {
    registerButton: vi.fn(),
    registerCommand: vi.fn(),
    dlg: vi.fn(),
    selection: { insertHTML: vi.fn() },
    events: { fire: vi.fn() },
    o: { theme: 'default' },
  };

  const resetMockChain = () => {
    mockDialog.setMod.mockReturnThis();
    mockDialog.setHeader.mockReturnThis();
    mockDialog.setContent.mockReturnThis();
    mockDialog.setSize.mockReturnThis();
    mockEditor.dlg.mockReturnValue(mockDialog);
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
    document.body.innerHTML = '';
    resetMockChain();
  });

  const importPlugin = async () => {
    await import('./code-snippet');
  };

  const getPluginCallback = (): ((editor: typeof mockEditor) => void) => {
    return mockPluginsAdd.mock.calls[0][1] as (editor: typeof mockEditor) => void;
  };

  const getCommandFn = (): (() => void) => {
    return mockEditor.registerCommand.mock.calls[0][1] as () => void;
  };

  it('should register icon via Jodit.modules.Icon.set with name codeSnippet', async () => {
    await importPlugin();

    expect(mockIconSet).toHaveBeenCalledWith('codeSnippet', expect.any(String));
  });

  it('should register plugin via Jodit.plugins.add with name codeSnippet', async () => {
    await importPlugin();

    expect(mockPluginsAdd).toHaveBeenCalledWith('codeSnippet', expect.any(Function));
  });

  it('should register button with tooltip Insert Source Code Snippet', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    expect(mockEditor.registerButton).toHaveBeenCalledWith({
      name: 'codeSnippet',
      group: 'insert',
      options: {
        tooltip: 'Insert Source Code Snippet',
      },
    });
  });

  it('should register command named codeSnippet', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    expect(mockEditor.registerCommand).toHaveBeenCalledWith('codeSnippet', expect.any(Function));
  });

  it('should create and open dialog with correct header', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    document.body.innerHTML = `
      <select id="programming-language"><option value="javascript">JavaScript</option></select>
      <textarea id="code"></textarea>
      <button id="add-code-snippet-button">Add</button>
    `;

    commandFn();

    expect(mockEditor.dlg).toHaveBeenCalledWith({ closeOnClickOverlay: true });
    expect(mockDialog.setHeader).toHaveBeenCalledWith('Insert Source Code Snippet');
    expect(mockDialog.setContent).toHaveBeenCalledWith(expect.any(String));
    expect(mockDialog.open).toHaveBeenCalled();
  });

  it('should insert pre/code HTML with selected language class on add button click', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    document.body.innerHTML = `
      <select id="programming-language">
        <option value="javascript" selected>JavaScript</option>
        <option value="python">Python</option>
      </select>
      <textarea id="code">console.log("hello")</textarea>
      <button id="add-code-snippet-button">Add</button>
    `;

    commandFn();

    const addButton = document.getElementById('add-code-snippet-button') as HTMLButtonElement;
    addButton.click();

    expect(mockEditor.selection.insertHTML).toHaveBeenCalledWith(
      '<pre><code class="language-javascript">console.log(&quot;hello&quot;)</code></pre>'
    );
  });

  it('should HTML-encode special characters (& < > " \')', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    document.body.innerHTML = `
      <select id="programming-language">
        <option value="html" selected>HTML</option>
      </select>
      <textarea id="code"></textarea>
      <button id="add-code-snippet-button">Add</button>
    `;

    const textarea = document.getElementById('code') as HTMLTextAreaElement;
    textarea.value = '&<>"\'';

    commandFn();

    const addButton = document.getElementById('add-code-snippet-button') as HTMLButtonElement;
    addButton.click();

    const insertedHtml = mockEditor.selection.insertHTML.mock.calls[0][0] as string;
    expect(insertedHtml).toContain('&amp;');
    expect(insertedHtml).toContain('&lt;');
    expect(insertedHtml).toContain('&gt;');
    expect(insertedHtml).toContain('&quot;');
    expect(insertedHtml).toContain('&#039;');
  });

  it('should fire change event and close dialog on add button click', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    document.body.innerHTML = `
      <select id="programming-language"><option value="python" selected>Python</option></select>
      <textarea id="code">print("hi")</textarea>
      <button id="add-code-snippet-button">Add</button>
    `;

    commandFn();

    const addButton = document.getElementById('add-code-snippet-button') as HTMLButtonElement;
    addButton.click();

    expect(mockEditor.events.fire).toHaveBeenCalledWith('change');
    expect(mockDialog.close).toHaveBeenCalled();
  });
});
