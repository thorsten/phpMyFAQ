import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';

const mockAutocomplete = vi.fn();

vi.mock('autocompleter', () => ({ default: mockAutocomplete }));
vi.mock('../api', () => ({ fetchFaqsByAutocomplete: vi.fn() }));
vi.mock('../../../../assets/src/utils', () => ({
  addElement: (tag: string, properties: Record<string, unknown> = {}, children: Node[] = []) => {
    const element = Object.assign(document.createElement(tag), properties);
    Object.keys(properties).forEach((key: string): void => {
      if (key.startsWith('data-')) {
        const dataKey: string = key.replace('data-', '');
        element.dataset[dataKey] = properties[key] as string;
      }
    });
    children.forEach((child: Node): Node => element.appendChild(child));
    return element;
  },
}));

describe('faqs.autocomplete', () => {
  let domContentLoadedCallback: (() => void) | null = null;

  beforeEach(() => {
    vi.resetModules();
    mockAutocomplete.mockClear();
    document.body.innerHTML = '';
    domContentLoadedCallback = null;

    // Spy on addEventListener to capture the DOMContentLoaded callback
    const originalAddEventListener = document.addEventListener.bind(document);
    vi.spyOn(document, 'addEventListener').mockImplementation(
      (type: string, listener: EventListenerOrEventListenerObject, options?: boolean | AddEventListenerOptions) => {
        if (type === 'DOMContentLoaded' && typeof listener === 'function') {
          domContentLoadedCallback = listener as () => void;
        } else {
          originalAddEventListener(type, listener, options);
        }
      }
    );
  });

  const importAndTrigger = async (): Promise<void> => {
    await import('./faqs.autocomplete');
    if (domContentLoadedCallback) {
      domContentLoadedCallback();
    }
  };

  it('should not initialize autocomplete when search input is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await importAndTrigger();

    expect(mockAutocomplete).not.toHaveBeenCalled();
  });

  it('should initialize autocomplete when search input exists', async () => {
    document.body.innerHTML = `
      <input id="pmf-faq-overview-search-input" type="text" />
      <input id="pmf-csrf-token" value="test-csrf-token" />
    `;

    await importAndTrigger();

    expect(mockAutocomplete).toHaveBeenCalledTimes(1);
  });

  it('should pass correct configuration to autocomplete', async () => {
    document.body.innerHTML = `
      <input id="pmf-faq-overview-search-input" type="text" />
      <input id="pmf-csrf-token" value="my-csrf" />
    `;

    await importAndTrigger();

    expect(mockAutocomplete).toHaveBeenCalledTimes(1);

    const config = mockAutocomplete.mock.calls[0][0] as Record<string, unknown>;
    const inputElement = document.getElementById('pmf-faq-overview-search-input');

    expect(config.input).toBe(inputElement);
    expect(config.minLength).toBe(1);
    expect(config.emptyMsg).toBe('No users found');
    expect(typeof config.onSelect).toBe('function');
    expect(typeof config.fetch).toBe('function');
    expect(typeof config.render).toBe('function');
  });

  it('should pass onSelect that navigates to adminUrl', async () => {
    document.body.innerHTML = `
      <input id="pmf-faq-overview-search-input" type="text" />
      <input id="pmf-csrf-token" value="csrf-123" />
    `;

    const hrefSetter = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true,
      configurable: true,
    });
    Object.defineProperty(window.location, 'href', {
      set: hrefSetter,
      get: () => '',
      configurable: true,
    });

    await importAndTrigger();

    const config = mockAutocomplete.mock.calls[0][0] as Record<string, unknown>;
    const onSelect = config.onSelect as (item: { adminUrl: string }) => void;

    onSelect({ adminUrl: '/admin/faq/edit/1' });

    expect(hrefSetter).toHaveBeenCalledWith('/admin/faq/edit/1');
  });

  it('should pass render that creates a div with highlighted match', async () => {
    document.body.innerHTML = `
      <input id="pmf-faq-overview-search-input" type="text" />
      <input id="pmf-csrf-token" value="csrf-456" />
    `;

    await importAndTrigger();

    const config = mockAutocomplete.mock.calls[0][0] as Record<string, unknown>;
    const render = config.render as (item: { question: string }, currentValue: string) => HTMLDivElement;

    const div = render({ question: 'How to install phpMyFAQ?' }, 'install');

    expect(div.tagName).toBe('DIV');
    expect(div.classList.contains('pmf-faq-list-result')).toBe(true);
    expect(div.classList.contains('border')).toBe(true);
    expect(div.innerHTML).toContain('<strong>install</strong>');
    expect(div.innerHTML).toContain('How to');
    expect(div.innerHTML).toContain('phpMyFAQ?');
  });

  it('should pass fetch that calls fetchFaqsByAutocomplete and filters results', async () => {
    document.body.innerHTML = `
      <input id="pmf-faq-overview-search-input" type="text" />
      <input id="pmf-csrf-token" value="csrf-789" />
    `;

    const { fetchFaqsByAutocomplete } = await import('../api');
    (fetchFaqsByAutocomplete as Mock).mockResolvedValue({
      success: [
        { question: 'How to install?', adminUrl: '/admin/1' },
        { question: 'How to configure?', adminUrl: '/admin/2' },
        { question: 'Release notes', adminUrl: '/admin/3' },
      ],
    });

    await importAndTrigger();

    const config = mockAutocomplete.mock.calls[0][0] as Record<string, unknown>;
    const fetchFn = config.fetch as (
      text: string,
      update: (items: Array<{ question: string; adminUrl: string }>) => void
    ) => Promise<void>;

    const update = vi.fn();
    await fetchFn('how', update);

    expect(fetchFaqsByAutocomplete).toHaveBeenCalledWith('how', 'csrf-789');
    expect(update).toHaveBeenCalledTimes(1);

    const filteredItems = update.mock.calls[0][0] as Array<{ question: string }>;
    expect(filteredItems).toHaveLength(2);
    expect(filteredItems[0].question).toBe('How to install?');
    expect(filteredItems[1].question).toBe('How to configure?');
  });
});
