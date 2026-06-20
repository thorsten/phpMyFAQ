import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../utils', () => ({
  getShortcutHintLabel: vi.fn(() => '⌘ K'),
}));

import { initSearchShortcutBadge } from './searchShortcutBadge';

describe('initSearchShortcutBadge', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('does nothing when the hint element is absent', () => {
    expect(() => initSearchShortcutBadge()).not.toThrow();
  });

  it('sets the hint label from getShortcutHintLabel', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" /><kbd id="pmf-search-hint"></kbd>';
    initSearchShortcutBadge();
    expect(document.getElementById('pmf-search-hint')?.textContent).toBe('⌘ K');
  });

  it('hides the hint initially when the input is pre-filled', () => {
    document.body.innerHTML =
      '<input id="pmf-search-autocomplete" value="prefilled" /><kbd id="pmf-search-hint"></kbd>';
    initSearchShortcutBadge();
    expect(document.getElementById('pmf-search-hint')?.classList.contains('d-none')).toBe(true);
  });

  it('shows the hint initially when the input is empty and not focused', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" /><kbd id="pmf-search-hint"></kbd>';
    initSearchShortcutBadge();
    expect(document.getElementById('pmf-search-hint')?.classList.contains('d-none')).toBe(false);
  });

  it('hides the hint on focus and shows it again on blur', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" /><kbd id="pmf-search-hint"></kbd>';
    const input = document.getElementById('pmf-search-autocomplete') as HTMLInputElement;
    const hint = document.getElementById('pmf-search-hint') as HTMLElement;
    initSearchShortcutBadge();

    input.dispatchEvent(new Event('focus'));
    // jsdom focus event does not set document.activeElement; call focus() too
    input.focus();
    input.dispatchEvent(new Event('focus'));
    expect(hint.classList.contains('d-none')).toBe(true);

    input.blur();
    input.dispatchEvent(new Event('blur'));
    expect(hint.classList.contains('d-none')).toBe(false);
  });

  it('hides the hint when the user types', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" /><kbd id="pmf-search-hint"></kbd>';
    const input = document.getElementById('pmf-search-autocomplete') as HTMLInputElement;
    const hint = document.getElementById('pmf-search-hint') as HTMLElement;
    initSearchShortcutBadge();

    input.value = 'mac';
    input.dispatchEvent(new Event('input'));
    expect(hint.classList.contains('d-none')).toBe(true);
  });
});
