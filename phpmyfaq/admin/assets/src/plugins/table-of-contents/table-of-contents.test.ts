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

const createRoot = (html: string): HTMLElement => {
  const root = document.createElement('div');
  root.innerHTML = html;
  return root;
};

const linksOf = (nav: HTMLElement): string[] =>
  Array.from(nav.querySelectorAll('a')).map((link) => `${link.getAttribute('href')} ${link.textContent}`);

describe('table-of-contents.svg', () => {
  it('should export a string containing SVG markup', async () => {
    const { default: svgContent } = await import('./table-of-contents.svg.js');
    expect(typeof svgContent).toBe('string');
    expect(svgContent).toContain('<svg');
  });
});

describe('buildTableOfContents', () => {
  beforeEach(() => {
    vi.resetModules();
  });

  it('should return null when the content has no headings', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<p>No headings here</p>');

    expect(buildTableOfContents(root)).toBeNull();
  });

  it('should ignore headings without text', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2> </h2><p>Text</p>');

    expect(buildTableOfContents(root)).toBeNull();
  });

  it('should build a nav with one link per heading', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2>Introduction</h2><p>Text</p><h2>Installation guide</h2>');

    const nav = buildTableOfContents(root);

    expect(nav?.tagName).toBe('NAV');
    expect(nav?.classList.contains('pmf-toc')).toBe(true);
    expect(linksOf(nav as HTMLElement)).toEqual([
      '#toc-introduction Introduction',
      '#toc-installation-guide Installation guide',
    ]);
  });

  it('should assign the generated ids to the headings', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2>Introduction</h2>');

    buildTableOfContents(root);

    expect(root.querySelector('h2')?.id).toBe('toc-introduction');
  });

  it('should keep an existing heading id', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2 id="custom-anchor">Introduction</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(root.querySelector('h2')?.id).toBe('custom-anchor');
    expect(linksOf(nav)).toEqual(['#custom-anchor Introduction']);
  });

  it('should make duplicate ids unique', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<p id="toc-setup">Taken</p><h2>Setup</h2><h2>Setup</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(linksOf(nav)).toEqual(['#toc-setup-2 Setup', '#toc-setup-3 Setup']);
  });

  it('should keep Unicode letters and drop punctuation in ids', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2>Häufige Fragen?</h2><h2>常见问题</h2><h2>Что нового!</h2><h2>???</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(linksOf(nav)).toEqual([
      '#toc-häufige-fragen Häufige Fragen?',
      '#toc-常见问题 常见问题',
      '#toc-что-нового Что нового!',
      '#toc-heading ???',
    ]);
  });

  it('should nest headings by level', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2>A</h2><h3>A.1</h3><h3>A.2</h3><h2>B</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(nav.innerHTML).toBe(
      '<ul><li><a href="#toc-a">A</a><ul><li><a href="#toc-a1">A.1</a></li><li><a href="#toc-a2">A.2</a></li></ul></li>' +
        '<li><a href="#toc-b">B</a></li></ul>'
    );
  });

  it('should not create empty items when heading levels are skipped', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h2>A</h2><h4>Deep</h4><h3>Middle</h3><h2>B</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(nav.innerHTML).toBe(
      '<ul><li><a href="#toc-a">A</a><ul><li><a href="#toc-deep">Deep</a></li><li><a href="#toc-middle">Middle</a></li></ul></li>' +
        '<li><a href="#toc-b">B</a></li></ul>'
    );
  });

  it('should keep a heading that is higher than the first one on the top level', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<h3>Sub</h3><h2>Main</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(nav.innerHTML).toBe('<ul><li><a href="#toc-sub">Sub</a></li><li><a href="#toc-main">Main</a></li></ul>');
  });

  it('should ignore headings inside an existing table of contents', async () => {
    const { buildTableOfContents } = await import('./table-of-contents');
    const root = createRoot('<nav class="pmf-toc"><h2>Contents</h2></nav><h2>Introduction</h2>');

    const nav = buildTableOfContents(root) as HTMLElement;

    expect(linksOf(nav)).toEqual(['#toc-introduction Introduction']);
  });
});

describe('table-of-contents plugin', () => {
  let root: HTMLElement;
  let mockEditor: {
    registerButton: ReturnType<typeof vi.fn>;
    registerCommand: ReturnType<typeof vi.fn>;
    editor: HTMLElement;
    selection: { insertHTML: ReturnType<typeof vi.fn> };
    message: { info: ReturnType<typeof vi.fn> };
    synchronizeValues: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();

    root = document.createElement('div');
    mockEditor = {
      registerButton: vi.fn(),
      registerCommand: vi.fn(),
      editor: root,
      selection: { insertHTML: vi.fn() },
      message: { info: vi.fn() },
      synchronizeValues: vi.fn(),
    };
  });

  const setUpPlugin = async (): Promise<() => void> => {
    await import('./table-of-contents');
    const pluginCallback = mockPluginsAdd.mock.calls[0][1] as (editor: typeof mockEditor) => void;
    pluginCallback(mockEditor);

    return mockEditor.registerCommand.mock.calls[0][1] as () => void;
  };

  it('should register icon and plugin with name tableOfContents', async () => {
    await import('./table-of-contents');

    expect(mockIconSet).toHaveBeenCalledWith('tableOfContents', expect.any(String));
    expect(mockPluginsAdd).toHaveBeenCalledWith('tableOfContents', expect.any(Function));
  });

  it('should register button with tooltip Insert Table of Contents', async () => {
    await setUpPlugin();

    expect(mockEditor.registerButton).toHaveBeenCalledWith({
      name: 'tableOfContents',
      group: 'insert',
      options: {
        tooltip: 'Insert Table of Contents',
      },
    });
  });

  it('should insert the table of contents at the cursor', async () => {
    root.innerHTML = '<h2>Introduction</h2>';
    const command = await setUpPlugin();

    command();

    expect(mockEditor.selection.insertHTML).toHaveBeenCalledWith(
      '<nav class="pmf-toc"><ul><li><a href="#toc-introduction">Introduction</a></li></ul></nav>'
    );
  });

  it('should replace an existing table of contents instead of inserting a second one', async () => {
    root.innerHTML =
      '<nav class="pmf-toc"><ul><li><a href="#toc-old">Old</a></li></ul></nav><h2>Introduction</h2><h2>Usage</h2>';
    const command = await setUpPlugin();

    command();

    expect(mockEditor.selection.insertHTML).not.toHaveBeenCalled();
    expect(root.querySelectorAll('nav.pmf-toc')).toHaveLength(1);
    expect(linksOf(root.querySelector('nav.pmf-toc') as HTMLElement)).toEqual([
      '#toc-introduction Introduction',
      '#toc-usage Usage',
    ]);
    expect(mockEditor.synchronizeValues).toHaveBeenCalled();
  });

  it('should show a message and insert nothing when there are no headings', async () => {
    root.innerHTML = '<p>Text</p>';
    const command = await setUpPlugin();

    command();

    expect(mockEditor.message.info).toHaveBeenCalledWith(
      'No headings found. Add headings to the content first, then insert the table of contents.'
    );
    expect(mockEditor.selection.insertHTML).not.toHaveBeenCalled();
    expect(root.innerHTML).toBe('<p>Text</p>');
  });
});
