import { describe, expect, it } from 'vitest';
import { highlightMatch } from './highlight';

const toHtml = (fragment: DocumentFragment): string => {
  const wrapper = document.createElement('div');
  wrapper.appendChild(fragment);
  return wrapper.innerHTML;
};

describe('highlightMatch', () => {
  it('wraps the matched substring in <strong>', () => {
    expect(toHtml(highlightMatch('Install phpMyFAQ', 'install'))).toBe('<strong>Install</strong> phpMyFAQ');
  });

  it('matches case-insensitively in the middle of the text', () => {
    expect(toHtml(highlightMatch('How to MAC setup', 'mac'))).toBe('How to <strong>MAC</strong> setup');
  });

  it('returns the plain text when there is no match', () => {
    expect(toHtml(highlightMatch('Hello world', 'xyz'))).toBe('Hello world');
  });

  it('returns the plain text for an empty query', () => {
    expect(toHtml(highlightMatch('Hello world', ''))).toBe('Hello world');
  });

  it('escapes HTML so it cannot inject markup', () => {
    expect(toHtml(highlightMatch('<img src=x> term', 'term'))).toBe('&lt;img src=x&gt; <strong>term</strong>');
  });

  it('treats regex metacharacters in the query as literals', () => {
    expect(toHtml(highlightMatch('a.b.c', '.'))).toBe('a<strong>.</strong>b<strong>.</strong>c');
  });
});
