import { beforeEach, describe, expect, it, vi } from 'vitest';

const showSpy = vi.fn();
const hideSpy = vi.fn();
vi.mock('bootstrap', () => ({
  Modal: vi.fn(function () {
    return { show: showSpy, hide: hideSpy };
  }),
}));

vi.mock('./autocomplete', () => ({
  attachAutocomplete: vi.fn(),
}));

describe('openSearchModal', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
    document.body.innerHTML = '';
  });

  it('creates the modal markup with a search input on first open', async () => {
    const { openSearchModal } = await import('./searchModal');
    openSearchModal();
    const input = document.getElementById('pmf-search-modal-input');
    expect(input).not.toBeNull();
    expect(input?.tagName).toBe('INPUT');
  });

  it('wires the modal input with attachAutocomplete', async () => {
    const { openSearchModal } = await import('./searchModal');
    const { attachAutocomplete } = await import('./autocomplete');
    openSearchModal();
    expect(attachAutocomplete).toHaveBeenCalledTimes(1);
  });

  it('shows the Bootstrap modal', async () => {
    const { openSearchModal } = await import('./searchModal');
    const { Modal } = await import('bootstrap');
    openSearchModal();
    expect(Modal).toHaveBeenCalled();
    expect(showSpy).toHaveBeenCalled();
  });

  it('reuses the same modal markup on subsequent opens', async () => {
    const { openSearchModal } = await import('./searchModal');
    const { attachAutocomplete } = await import('./autocomplete');
    openSearchModal();
    openSearchModal();
    expect(document.querySelectorAll('#pmf-search-modal').length).toBe(1);
    expect(attachAutocomplete).toHaveBeenCalledTimes(1);
  });
});
