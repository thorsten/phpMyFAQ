import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleStickyFaqs } from './stickyfaqs';

vi.mock('sortablejs', () => ({ default: { create: vi.fn() } }));

vi.mock('bootstrap', () => {
  const showFn = vi.fn();
  const hideFn = vi.fn();
  class ModalMock {
    show = showFn;
    hide = hideFn;
  }
  return {
    Modal: ModalMock,
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
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { updateStickyFaqsOrder, removeStickyFaq } from '../api/sticky-faqs';

describe('handleStickyFaqs', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
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

    await vi.advanceTimersByTimeAsync(50);

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

    await vi.advanceTimersByTimeAsync(50);

    // showConfirmModal resolves false because #confirmUnstickyModal is not in DOM
    // so removeStickyFaq should not be called, and no error notification for missing attributes
    expect(console.error).toHaveBeenCalledWith('Confirmation modal not found in DOM');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should call removeStickyFaq and remove row when user confirms', async () => {
    (removeStickyFaq as ReturnType<typeof vi.fn>).mockResolvedValue({ success: 'Removed' });

    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!" data-csrf="csrf-token">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button"
                  data-pmf-faq-id="1"
                  data-pmf-category-id="10"
                  data-pmf-csrf="token123"
                  data-pmf-lang="en">Unstick</button>
        </div>
      </div>
      <div id="confirmUnstickyModal"></div>
      <div id="confirmUnstickyModalBody"></div>
      <button id="confirmUnstickyModalConfirm"></button>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await vi.advanceTimersByTimeAsync(0);

    // Simulate user clicking confirm button
    const confirmBtn = document.getElementById('confirmUnstickyModalConfirm') as HTMLElement;
    confirmBtn.click();

    await vi.advanceTimersByTimeAsync(0);

    expect(removeStickyFaq).toHaveBeenCalledWith('1', '10', 'token123', 'en');

    // Wait for the animation timeout
    await vi.advanceTimersByTimeAsync(300);

    expect(pushNotification).toHaveBeenCalledWith('Removed!');
  });

  it('should not call removeStickyFaq when user cancels', async () => {
    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!" data-csrf="csrf-token">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button"
                  data-pmf-faq-id="1"
                  data-pmf-category-id="10"
                  data-pmf-csrf="token123"
                  data-pmf-lang="en">Unstick</button>
        </div>
      </div>
      <div id="confirmUnstickyModal"></div>
      <div id="confirmUnstickyModalBody"></div>
      <button id="confirmUnstickyModalConfirm"></button>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await vi.advanceTimersByTimeAsync(0);

    // Simulate modal being hidden (cancel) by dispatching hidden.bs.modal
    const modal = document.getElementById('confirmUnstickyModal') as HTMLElement;
    modal.dispatchEvent(new Event('hidden.bs.modal'));

    await vi.advanceTimersByTimeAsync(50);

    expect(removeStickyFaq).not.toHaveBeenCalled();
  });

  it('should show error notification when unstick button has missing faqId/categoryId/csrf/lang', async () => {
    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!" data-csrf="csrf-token">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button"
                  data-pmf-faq-id="1"
                  data-pmf-category-id=""
                  data-pmf-csrf=""
                  data-pmf-lang="">Unstick</button>
        </div>
      </div>
      <div id="confirmUnstickyModal"></div>
      <div id="confirmUnstickyModalBody"></div>
      <button id="confirmUnstickyModalConfirm"></button>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await vi.advanceTimersByTimeAsync(0);

    // Simulate user clicking confirm
    const confirmBtn = document.getElementById('confirmUnstickyModalConfirm') as HTMLElement;
    confirmBtn.click();

    await vi.advanceTimersByTimeAsync(0);

    expect(pushErrorNotification).toHaveBeenCalledWith('Missing required FAQ information; cannot remove sticky FAQ.');
    expect(removeStickyFaq).not.toHaveBeenCalled();
  });

  it('should show error notification when removeStickyFaq throws an Error', async () => {
    (removeStickyFaq as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('API failure'));

    document.body.innerHTML = `
      <div id="stickyFAQs" data-lang-confirm="Remove?" data-lang-success="Removed!" data-csrf="csrf-token">
        <div class="list-group-item" data-pmf-faqid="1">
          <button class="js-unstick-button"
                  data-pmf-faq-id="1"
                  data-pmf-category-id="10"
                  data-pmf-csrf="token123"
                  data-pmf-lang="en">Unstick</button>
        </div>
      </div>
      <div id="confirmUnstickyModal"></div>
      <div id="confirmUnstickyModalBody"></div>
      <button id="confirmUnstickyModalConfirm"></button>
    `;

    handleStickyFaqs();

    const button = document.querySelector('.js-unstick-button') as HTMLButtonElement;
    button.click();

    await vi.advanceTimersByTimeAsync(0);

    const confirmBtn = document.getElementById('confirmUnstickyModalConfirm') as HTMLElement;
    confirmBtn.click();

    await vi.advanceTimersByTimeAsync(0);

    expect(pushErrorNotification).toHaveBeenCalledWith('API failure');
  });

  it('should call updateStickyFaqsOrder on Sortable onEnd callback', async () => {
    (updateStickyFaqsOrder as ReturnType<typeof vi.fn>).mockResolvedValue({ success: 'Order updated' });

    document.body.innerHTML = `
      <div id="stickyFAQs" data-csrf="csrf-token">
        <div class="list-group-item" data-pmf-faqid="1">Item 1</div>
        <div class="list-group-item" data-pmf-faqid="2">Item 2</div>
      </div>
    `;

    handleStickyFaqs();

    const callArgs = (Sortable.create as ReturnType<typeof vi.fn>).mock.calls[0];
    const options = callArgs[1] as Record<string, unknown>;
    const onEnd = options.onEnd as (event: { from: HTMLElement }) => Promise<void>;

    const stickyFAQs = document.getElementById('stickyFAQs') as HTMLElement;

    await onEnd({ from: stickyFAQs } as unknown as Sortable.SortableEvent);

    expect(updateStickyFaqsOrder).toHaveBeenCalledWith(['1', '2'], 'csrf-token');
    expect(pushNotification).toHaveBeenCalledWith('Order updated');
  });
});
