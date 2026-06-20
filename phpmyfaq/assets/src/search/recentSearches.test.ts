import { beforeEach, describe, expect, it, vi } from 'vitest';
import { addRecentSearch, clearRecentSearches, getRecentSearches } from './recentSearches';

describe('recentSearches', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('returns an empty array when nothing is stored', () => {
    expect(getRecentSearches()).toEqual([]);
  });

  it('stores and returns a term', () => {
    addRecentSearch('mac');
    expect(getRecentSearches()).toEqual(['mac']);
  });

  it('puts the most recent term first', () => {
    addRecentSearch('one');
    addRecentSearch('two');
    expect(getRecentSearches()).toEqual(['two', 'one']);
  });

  it('deduplicates case-insensitively and moves the term to the front', () => {
    addRecentSearch('Mac');
    addRecentSearch('linux');
    addRecentSearch('mac');
    expect(getRecentSearches()).toEqual(['mac', 'linux']);
  });

  it('caps the list at five entries', () => {
    ['a', 'b', 'c', 'd', 'e', 'f'].forEach(addRecentSearch);
    expect(getRecentSearches()).toEqual(['f', 'e', 'd', 'c', 'b']);
  });

  it('ignores blank terms', () => {
    addRecentSearch('   ');
    addRecentSearch('');
    expect(getRecentSearches()).toEqual([]);
  });

  it('clears stored terms', () => {
    addRecentSearch('mac');
    clearRecentSearches();
    expect(getRecentSearches()).toEqual([]);
  });

  it('returns an empty array when localStorage.getItem throws', () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('blocked');
    });
    expect(getRecentSearches()).toEqual([]);
    vi.restoreAllMocks();
  });

  it('does not throw when localStorage.setItem throws', () => {
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('blocked');
    });
    expect(() => addRecentSearch('mac')).not.toThrow();
    vi.restoreAllMocks();
  });
});
