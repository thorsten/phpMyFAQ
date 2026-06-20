import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('autocompleter', () => ({
  default: vi.fn(),
}));

vi.mock('../api', () => ({
  fetchAutoCompleteData: vi.fn(),
  fetchPopularSearches: vi.fn(),
}));

vi.mock('./recentSearches', () => ({
  getRecentSearches: vi.fn(() => []),
  addRecentSearch: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string> = {}, children: Node[] = []) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    // jsdom does not reflect innerText into textContent, so treat it as text content.
    if (props.innerText) el.textContent = props.innerText;
    if (props.textContent) el.textContent = props.textContent;
    children.forEach((child) => el.appendChild(child));
    return el;
  }),
  TranslationService: class {
    async loadTranslations(): Promise<void> {}
    translate(key: string): string {
      return key;
    }
  },
}));

import { attachAutocomplete, handleAutoComplete } from './autocomplete';
import { fetchAutoCompleteData, fetchPopularSearches } from '../api';
import { getRecentSearches, addRecentSearch } from './recentSearches';
import { AutocompleteSearchResponse, Suggestion } from '../interfaces';
import autocomplete from 'autocompleter';

const mockAutocomplete = vi.mocked(autocomplete);
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getConfig = (): any => mockAutocomplete.mock.calls[0][0];

describe('attachAutocomplete', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.mocked(getRecentSearches).mockReturnValue([]);
    vi.mocked(fetchPopularSearches).mockResolvedValue([]);
    vi.mocked(fetchAutoCompleteData).mockResolvedValue([]);
  });

  it('initialises autocomplete on the given input', () => {
    const input = document.createElement('input');
    attachAutocomplete(input);
    expect(mockAutocomplete).toHaveBeenCalledTimes(1);
    expect(getConfig().input).toBe(input);
    expect(getConfig().showOnFocus).toBe(true);
    expect(getConfig().debounceWaitMs).toBe(200);
  });

  it('fetches live results mapped to result items when typing', async () => {
    vi.mocked(fetchAutoCompleteData).mockResolvedValue([
      { question: 'How to install?', category: 'Setup', url: '/faq/1' },
    ] as AutocompleteSearchResponse);
    attachAutocomplete(document.createElement('input'));

    const update = vi.fn();
    await getConfig().fetch('Install', update);

    expect(fetchAutoCompleteData).toHaveBeenCalledWith('install');
    const items = update.mock.calls[0][0] as Suggestion[];
    expect(items[0].type).toBe('result');
  });

  it('shows a no-results helper when a non-empty query returns nothing', async () => {
    vi.mocked(fetchAutoCompleteData).mockResolvedValue([]);
    attachAutocomplete(document.createElement('input'));

    const update = vi.fn();
    await getConfig().fetch('zzz', update);

    const items = update.mock.calls[0][0] as Suggestion[];
    expect(items).toHaveLength(1);
    expect(items[0].type).toBe('empty');
  });

  it('composes recent then popular items on empty input', async () => {
    vi.mocked(getRecentSearches).mockReturnValue(['mac']);
    vi.mocked(fetchPopularSearches).mockResolvedValue([{ id: 1, searchterm: 'linux', number: '9' }]);
    attachAutocomplete(document.createElement('input'));

    const update = vi.fn();
    await getConfig().fetch('', update);

    const items = update.mock.calls[0][0] as Suggestion[];
    expect(items.map((i) => i.type)).toEqual(['recent', 'popular']);
    expect(items[0].searchTerm).toBe('mac');
    expect(items[1].searchTerm).toBe('linux');
  });

  it('returns no empty-state items when there are no recent or popular searches', async () => {
    attachAutocomplete(document.createElement('input'));
    const update = vi.fn();
    await getConfig().fetch('', update);
    expect(update).toHaveBeenCalledWith([]);
  });

  it('records the term and navigates on select of a result', () => {
    const input = document.createElement('input');
    input.value = 'install';
    const mockLocation = { href: '' };
    Object.defineProperty(window, 'location', { value: mockLocation, writable: true });
    attachAutocomplete(input);

    getConfig().onSelect({ type: 'result', url: '/faq/42' } as Suggestion);

    expect(addRecentSearch).toHaveBeenCalledWith('install');
    expect(mockLocation.href).toBe('/faq/42');
  });

  it('records the search term and navigates on select of a popular item', () => {
    const mockLocation = { href: '' };
    Object.defineProperty(window, 'location', { value: mockLocation, writable: true });
    attachAutocomplete(document.createElement('input'));

    getConfig().onSelect({ type: 'popular', searchTerm: 'linux', url: 'search.html?search=linux' } as Suggestion);

    expect(addRecentSearch).toHaveBeenCalledWith('linux');
    expect(mockLocation.href).toBe('search.html?search=linux');
  });

  it('handleAutoComplete does nothing without the inline input', () => {
    document.body.innerHTML = '<div></div>';
    handleAutoComplete();
    expect(mockAutocomplete).not.toHaveBeenCalled();
  });

  it('handleAutoComplete attaches to the inline input when present', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';
    handleAutoComplete();
    expect(mockAutocomplete).toHaveBeenCalledTimes(1);
    expect(getConfig().input).toBe(document.getElementById('pmf-search-autocomplete'));
  });

  it('renders a no-results helper for an empty item', () => {
    attachAutocomplete(document.createElement('input'));
    const el = getConfig().render({ type: 'empty', url: 'search.html?search=x' } as Suggestion, '') as HTMLElement;
    expect(el.classList.contains('pmf-search-empty')).toBe(true);
    expect(el.textContent).toContain('msgNoSearchResults');
    expect(el.textContent).toContain('msgAskQuestionInstead');
  });

  it('renders a popular item with a count badge', () => {
    attachAutocomplete(document.createElement('input'));
    const el = getConfig().render(
      { type: 'popular', searchTerm: 'linux', count: 9, url: 'search.html?search=linux' } as Suggestion,
      ''
    ) as HTMLElement;
    expect(el.textContent).toContain('linux');
    expect(el.textContent).toContain('9x');
  });

  it('does not render a badge when the popular count is NaN', () => {
    attachAutocomplete(document.createElement('input'));
    const el = getConfig().render(
      { type: 'popular', searchTerm: 'linux', count: Number('abc'), url: 'search.html?search=linux' } as Suggestion,
      ''
    ) as HTMLElement;
    expect(el.textContent).toContain('linux');
    expect(el.textContent).not.toContain('NaN');
  });

  it('renders a result item and highlights the matched query', () => {
    attachAutocomplete(document.createElement('input'));
    const el = getConfig().render(
      { type: 'result', question: 'Install phpMyFAQ', category: 'Setup', url: '/faq/1' } as Suggestion,
      'install'
    ) as HTMLElement;
    expect(el.textContent).toContain('Setup');
    const strong = el.querySelector('strong');
    expect(strong?.textContent).toBe('Install');
  });
});
