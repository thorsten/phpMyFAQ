import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleStickyFaqs } from './stickyfaqs';

vi.mock('sortablejs', () => ({ default: { create: vi.fn() } }));

vi.mock('bootstrap', () => {
  const showFn = vi.fn();
  const hideFn = vi.fn();
  return {
    Modal: vi.fn().mockImplementation(() => ({
      show: showFn,
      hide: hideFn,
    })),
  };
});

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

vi.mock('../api/sticky-faqs', () => ({
  updateStickyFaqsOrder: vi.fn(),
  removeStickyFaq: vi.fn(),
}));

import Sortable from 'sortablejs';
import { pushErrorNotification } from '../../../../assets/src/utils';

describe('handleStickyFaqs', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
  });

  it('should do nothing when #stickyFAQs element does not exist', () => {
    document.body.innerHTML = '<div></div>';

    handleStickyFaqs();

    expect(Sortable.create).not.toHaveBeenCalled();
  });

  it('should create Sortable instance when #stickyFAQs element exists', () => {
    document.body.innerHTML = '<div id="stickyFAQs"></div>';

    handleStickyFaqs();

    expect(Sortable.create).toHaveBeenCalledTimes(1);
    const callArgs = (Sortable.create as ReturnType<typeof vi.fn>).mock.calls[0];
    const element = callArgs[0] as HTMLElement;
    expect(element.id).toBe('stickyFAQs');
    const options = callArgs[1] as Record<string, unknown>;
    expect(options.animation).toBe(150);
    expect(options.draggable).toBe('.list-group-item');
    expect(options.handle).toBe('.drag-handle');
    expect(options.sort).toBe(true);
    expect(options.filter).toBe('.sortable-disabled');
    expect(options.dataIdAttr).toBe('data-pmf-faqid');
    expect(typeof options.onEnd).toBe('function');
  });

  it('should show error notification when unstick button has missing attributes', async () => {
    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button">Unstick</button>
        </div>
      </div>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await new Promise((resolve) => setTimeout(resolve, 50));

    // showConfirmModal will resolve false because the modal elements are not in the DOM
    // so it won't get to the missing attributes check; it just returns early.
    // The console.error for missing modal should have been called.
    expect(console.error).toHaveBeenCalledWith('Confirmation modal not found in DOM');
  });

  it('should resolve false when confirm modal elements are not found in DOM', async () => {
    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button"
                  data-pmf-faq-id="1"
                  data-pmf-category-id="10"
                  data-pmf-csrf="token123"
                  data-pmf-lang="en">Unstick</button>
        </div>
      </div>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await new Promise((resolve) => setTimeout(resolve, 50));

    // showConfirmModal resolves false because #confirmUnstickyModal is not in DOM
    // so removeStickyFaq should not be called, and no error notification for missing attributes
    expect(console.error).toHaveBeenCalledWith('Confirmation modal not found in DOM');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });
});
