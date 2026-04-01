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

describe('show-blocks.svg', () => {
  it('should export a string containing SVG markup', async () => {
    const { default: svgContent } = await import('./show-blocks.svg.js');
    expect(typeof svgContent).toBe('string');
    expect(svgContent).toContain('<svg');
  });

  it('should contain visual elements', async () => {
    const { default: svgContent } = await import('./show-blocks.svg.js');
    expect(svgContent).toContain('<rect');
    expect(svgContent).toContain('<line');
  });
});

describe('show-blocks plugin', () => {
  const mockEditorDoc = {
    createElement: vi.fn(),
    head: { appendChild: vi.fn() },
  };

  const mockClassList = {
    add: vi.fn(),
    remove: vi.fn(),
  };

  const mockEditor = {
    registerButton: vi.fn(),
    registerCommand: vi.fn(),
    editor: {
      ownerDocument: mockEditorDoc,
      classList: mockClassList,
    },
    events: { fire: vi.fn() },
    o: { theme: 'default' },
  };

  let mockStyleElement: {
    setAttribute: ReturnType<typeof vi.fn>;
    textContent: string | null;
    remove: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();

    mockStyleElement = {
      setAttribute: vi.fn(),
      textContent: null,
      remove: vi.fn(),
    };

    mockEditorDoc.createElement.mockReturnValue(mockStyleElement);
  });

  const importPlugin = async () => {
    await import('./show-blocks');
  };

  const getPluginCallback = (): ((editor: typeof mockEditor) => void) => {
    return mockPluginsAdd.mock.calls[0][1] as (editor: typeof mockEditor) => void;
  };

  const getCommandFn = (): (() => void) => {
    return mockEditor.registerCommand.mock.calls[0][1] as () => void;
  };

  it('should register icon via Jodit.modules.Icon.set with name showBlocks', async () => {
    await importPlugin();

    expect(mockIconSet).toHaveBeenCalledWith('showBlocks', expect.any(String));
  });

  it('should register plugin via Jodit.plugins.add with name showBlocks', async () => {
    await importPlugin();

    expect(mockPluginsAdd).toHaveBeenCalledWith('showBlocks', expect.any(Function));
  });

  it('should register button with tooltip Show Blocks', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    expect(mockEditor.registerButton).toHaveBeenCalledWith({
      name: 'showBlocks',
      group: 'other',
      options: {
        tooltip: 'Show Blocks',
        isActive: expect.any(Function),
      },
    });
  });

  it('should register command named showBlocks', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    expect(mockEditor.registerCommand).toHaveBeenCalledWith('showBlocks', expect.any(Function));
  });

  it('should report inactive state initially via isActive', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const buttonConfig = mockEditor.registerButton.mock.calls[0][0] as {
      options: { isActive: () => boolean };
    };
    expect(buttonConfig.options.isActive()).toBe(false);
  });

  it('should add show-blocks class to editor on first toggle', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();
    commandFn();

    expect(mockClassList.add).toHaveBeenCalledWith('jodit-show-blocks');
  });

  it('should inject a style element on first toggle', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();
    commandFn();

    expect(mockEditorDoc.createElement).toHaveBeenCalledWith('style');
    expect(mockStyleElement.setAttribute).toHaveBeenCalledWith('data-jodit-show-blocks', '');
    expect(mockStyleElement.textContent).toBeTruthy();
    expect(mockEditorDoc.head.appendChild).toHaveBeenCalledWith(mockStyleElement);
  });

  it('should include CSS rules for common block tags', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();
    commandFn();

    const css = mockStyleElement.textContent as string;
    expect(css).toContain('.jodit-show-blocks p');
    expect(css).toContain('.jodit-show-blocks div');
    expect(css).toContain('.jodit-show-blocks h1');
    expect(css).toContain('.jodit-show-blocks blockquote');
    expect(css).toContain('.jodit-show-blocks table');
    expect(css).toContain('border: 1px dashed');
    expect(css).toContain('content: "p"');
    expect(css).toContain('content: "h1"');
  });

  it('should fire updateToolbar event on toggle', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();
    commandFn();

    expect(mockEditor.events.fire).toHaveBeenCalledWith('updateToolbar');
  });

  it('should report active state after first toggle via isActive', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();
    commandFn();

    const buttonConfig = mockEditor.registerButton.mock.calls[0][0] as {
      options: { isActive: () => boolean };
    };
    expect(buttonConfig.options.isActive()).toBe(true);
  });

  it('should remove show-blocks class and styles on second toggle', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    // Toggle on
    commandFn();
    // Toggle off
    commandFn();

    expect(mockClassList.remove).toHaveBeenCalledWith('jodit-show-blocks');
    expect(mockStyleElement.remove).toHaveBeenCalled();
  });

  it('should report inactive state after toggling off via isActive', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    // Toggle on then off
    commandFn();
    commandFn();

    const buttonConfig = mockEditor.registerButton.mock.calls[0][0] as {
      options: { isActive: () => boolean };
    };
    expect(buttonConfig.options.isActive()).toBe(false);
  });

  it('should not create duplicate style elements on repeated activations', async () => {
    await importPlugin();

    const pluginCallback = getPluginCallback();
    pluginCallback(mockEditor);

    const commandFn = getCommandFn();

    // Toggle on, off, on
    commandFn();
    commandFn();
    commandFn();

    // createElement should be called twice (once for first on, once for second on after removal)
    expect(mockEditorDoc.createElement).toHaveBeenCalledTimes(2);
  });
});
