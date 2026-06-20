import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('./searchModal', () => ({
  openSearchModal: vi.fn(),
}));

import { initSearchShortcut } from './searchShortcut';
import { openSearchModal } from './searchModal';

const pressK = (): KeyboardEvent => {
  const event = new KeyboardEvent('keydown', {
    key: 'k',
    metaKey: true,
    ctrlKey: true,
    cancelable: true,
    bubbles: true,
  });
  document.dispatchEvent(event);
  return event;
};

describe('initSearchShortcut', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('focuses the inline input when it exists', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';
    const input = document.getElementById('pmf-search-autocomplete') as HTMLInputElement;
    const focusSpy = vi.spyOn(input, 'focus');

    initSearchShortcut();
    const event = pressK();

    expect(focusSpy).toHaveBeenCalled();
    expect(openSearchModal).not.toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(true);
  });

  it('opens the modal when the inline input is absent', () => {
    initSearchShortcut();
    pressK();
    expect(openSearchModal).toHaveBeenCalledTimes(1);
  });

  it('ignores the shortcut when typing in another text field', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" /><textarea id="other"></textarea>';
    const other = document.getElementById('other') as HTMLTextAreaElement;
    other.focus();
    const focusSpy = vi.spyOn(document.getElementById('pmf-search-autocomplete') as HTMLInputElement, 'focus');

    initSearchShortcut();
    pressK();

    expect(focusSpy).not.toHaveBeenCalled();
    expect(openSearchModal).not.toHaveBeenCalled();
  });

  it('blurs the inline input on Escape', () => {
    document.body.innerHTML = '<input id="pmf-search-autocomplete" type="text" />';
    const input = document.getElementById('pmf-search-autocomplete') as HTMLInputElement;
    input.focus();
    const blurSpy = vi.spyOn(input, 'blur');

    initSearchShortcut();
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

    expect(blurSpy).toHaveBeenCalled();
  });
});
