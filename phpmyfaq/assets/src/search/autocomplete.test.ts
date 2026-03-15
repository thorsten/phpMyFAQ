import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('autocompleter', () => ({
  default: vi.fn(),
}));

vi.mock('../api', () => ({
  fetchAutoCompleteData: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>, children: Node[] = []) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    if (props.textContent) el.textContent = props.textContent;
    children.forEach((child) => el.appendChild(child));
    return el;
  }),
}));

import { handleAutoComplete } from './autocomplete';
import { fetchAutoCompleteData } from '../api';
import { Suggestion } from '../interfaces';
import autocomplete from 'autocompleter';

const mockAutocomplete = vi.mocked(autocomplete);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getConfig = (): any => mockAutocomplete.mock.calls[0][0];

describe('handleAutoComplete', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when autocomplete input is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleAutoComplete();

    expect(mockAutocomplete).not.toHaveBeenCalled();
  });

  it('should initialize autocomplete when input element exists', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    handleAutoComplete();

    expect(mockAutocomplete).toHaveBeenCalledTimes(1);
  });

  it('should pass the input element to autocomplete', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    handleAutoComplete();

    expect(getConfig().input).toBe(document.getElementById('pmf-search-autocomplete'));
  });

  it('should configure debounce wait time', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    handleAutoComplete();

    expect(getConfig().debounceWaitMs).toBe(200);
  });

  it('should fetch data and call update in the fetch callback', async () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    const mockResults: Suggestion[] = [
      { question: 'How to install?', category: 'Setup', url: '/faq/1' } as Suggestion,
      { question: 'How to configure?', category: 'Config', url: '/faq/2' } as Suggestion,
    ];
    vi.mocked(fetchAutoCompleteData).mockResolvedValue(mockResults);

    handleAutoComplete();

    const update = vi.fn();
    await getConfig().fetch('Test Query', update);

    expect(fetchAutoCompleteData).toHaveBeenCalledWith('test query');
    expect(update).toHaveBeenCalledWith(mockResults);
  });

  it('should convert search string to lowercase in fetch callback', async () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    vi.mocked(fetchAutoCompleteData).mockResolvedValue([]);

    handleAutoComplete();

    await getConfig().fetch('UPPERCASE Query', vi.fn());

    expect(fetchAutoCompleteData).toHaveBeenCalledWith('uppercase query');
  });

  it('should navigate to item URL on select', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    const mockLocation = { href: '' };
    Object.defineProperty(window, 'location', { value: mockLocation, writable: true });

    handleAutoComplete();

    const item: Suggestion = { question: 'FAQ', category: 'General', url: '/faq/42' } as Suggestion;
    getConfig().onSelect(item);

    expect(mockLocation.href).toBe('/faq/42');
  });

  it('should render suggestion items with category and question', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    handleAutoComplete();

    const item: Suggestion = {
      question: 'How to install phpMyFAQ?',
      category: 'Installation',
      url: '/faq/1',
    } as Suggestion;

    const rendered = getConfig().render(item) as HTMLElement;

    expect(rendered.tagName).toBe('LI');
    expect(rendered.classList.contains('list-group-item')).toBe(true);

    const categoryEl = rendered.querySelector('.fw-bold') as HTMLElement | null;
    expect(categoryEl?.innerText).toBe('Installation');

    const questionEl = rendered.querySelector('.pmf-searched-question');
    expect(questionEl?.textContent).toBe('How to install phpMyFAQ?');
  });

  it('should create a list-group container', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';

    handleAutoComplete();

    const container = getConfig().container as HTMLElement;
    expect(container).toBeDefined();
    expect(container.tagName).toBe('UL');
    expect(container.classList.contains('list-group')).toBe(true);
  });
});
