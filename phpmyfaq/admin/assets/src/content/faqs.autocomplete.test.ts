import { describe, it, expect, beforeEach, afterEach, vi, type Mock } from 'vitest';
import autocomplete from 'autocompleter';
import { escapeRegExp, highlightMatch, initFaqSearchAutocomplete } from './faqs.autocomplete';
import { fetchFaqsByAutocomplete } from '../api';

vi.mock('autocompleter', () => ({
  default: vi.fn(),
}));

vi.mock('../api', () => ({
  fetchFaqsByAutocomplete: vi.fn(),
}));

const mockAutocomplete = autocomplete as unknown as Mock;
const mockFetchFaqs = fetchFaqsByAutocomplete as unknown as Mock;

describe('escapeRegExp', () => {
  it('escapes regular expression metacharacters', () => {
    expect(escapeRegExp('a.b*c(d)[e]')).toBe('a\\.b\\*c\\(d\\)\\[e\\]');
  });
});

describe('highlightMatch', () => {
  it('wraps the matched term case-insensitively', () => {
    expect(highlightMatch('Reinigung Saalio und Saalux', 'reinigung')).toBe(
      '<strong>Reinigung</strong> Saalio und Saalux'
    );
  });

  it('does not throw for terms with regular expression metacharacters', () => {
    expect(() => highlightMatch('What is (foo)?', '(foo')).not.toThrow();
    expect(highlightMatch('What is (foo)?', '(foo')).toBe('What is <strong>(foo</strong>)?');
  });

  it('escapes HTML in the question', () => {
    expect(highlightMatch('<b>bold</b> text', 'text')).toBe('&lt;b&gt;bold&lt;/b&gt; <strong>text</strong>');
  });

  it('returns the escaped question for an empty term', () => {
    expect(highlightMatch('a & b', '   ')).toBe('a &amp; b');
  });
});

describe('initFaqSearchAutocomplete', () => {
  let fetchSpy: Mock;

  beforeEach(() => {
    vi.clearAllMocks();
    fetchSpy = vi.fn();
    mockAutocomplete.mockReturnValue({ fetch: fetchSpy, destroy: vi.fn() });

    document.body.innerHTML = `
      <form id="pmf-faq-search-autocomplete" action="./faqs" method="get">
        <input type="hidden" id="pmf-csrf-token" name="pmf-csrf-token" value="token" />
        <input type="text" id="pmf-faq-overview-search-input" data-pmf-empty-msg="No results found" />
        <button type="button" id="pmf-faq-overview-search-button">Search</button>
      </form>
    `;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  it('does nothing when the search input is missing', () => {
    document.body.innerHTML = '';
    initFaqSearchAutocomplete();
    expect(mockAutocomplete).not.toHaveBeenCalled();
  });

  it('configures the autocompleter to always prevent form submission', () => {
    initFaqSearchAutocomplete();

    const options = mockAutocomplete.mock.calls[0][0];
    expect(options.preventSubmit).toBe(1);
    expect(options.emptyMsg).toBe('No results found');
    expect(options.minLength).toBe(1);
  });

  it('prevents the native form submit and triggers a fetch instead', () => {
    initFaqSearchAutocomplete();

    const form = document.getElementById('pmf-faq-search-autocomplete') as HTMLFormElement;
    const event = new Event('submit', { cancelable: true });
    form.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(fetchSpy).toHaveBeenCalledTimes(1);
  });

  it('triggers a fetch when the search button is clicked', () => {
    initFaqSearchAutocomplete();

    const button = document.getElementById('pmf-faq-overview-search-button') as HTMLButtonElement;
    button.click();

    expect(fetchSpy).toHaveBeenCalledTimes(1);
    expect(document.activeElement?.id).toBe('pmf-faq-overview-search-input');
  });

  it('passes every server result to the autocompleter without filtering by question', async () => {
    const results = [
      { question: 'Reinigung Saalio und Saalux', adminUrl: './faq/edit/1/de' },
      { question: 'Wartung', adminUrl: './faq/edit/2/de' },
    ];
    mockFetchFaqs.mockResolvedValue({ success: results });
    initFaqSearchAutocomplete();

    const options = mockAutocomplete.mock.calls[0][0];
    const update = vi.fn();
    await options.fetch('reinigung', update);

    expect(mockFetchFaqs).toHaveBeenCalledWith('reinigung', 'token');
    expect(update).toHaveBeenCalledWith(results);
  });

  it('sends the trimmed search term and skips blank input', async () => {
    mockFetchFaqs.mockResolvedValue({ success: [] });
    initFaqSearchAutocomplete();

    const options = mockAutocomplete.mock.calls[0][0];
    const update = vi.fn();

    await options.fetch('  rein ', update);
    expect(mockFetchFaqs).toHaveBeenCalledWith('rein', 'token');

    mockFetchFaqs.mockClear();
    await options.fetch('   ', update);
    expect(mockFetchFaqs).not.toHaveBeenCalled();
    expect(update).toHaveBeenLastCalledWith([]);
  });

  it('shows an empty list when the request fails', async () => {
    mockFetchFaqs.mockRejectedValue(new Error('Network response was not ok.'));
    initFaqSearchAutocomplete();

    const options = mockAutocomplete.mock.calls[0][0];
    const update = vi.fn();
    await options.fetch('rein', update);

    expect(update).toHaveBeenCalledWith([]);
  });
});
