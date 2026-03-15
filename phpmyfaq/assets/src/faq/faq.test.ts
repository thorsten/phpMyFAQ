import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../utils', () => ({
  addElement: vi.fn((_tag: string, props: Record<string, string>) => {
    const el = document.createElement(_tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

vi.mock('../api', () => ({
  createFaq: vi.fn(),
  createBookmark: vi.fn(),
  deleteBookmark: vi.fn(),
}));

import { handleAddFaq, handleShowFaq, handleShareLinkButton } from './faq';
import { createFaq, createBookmark, deleteBookmark } from '../api';
import { pushNotification, pushErrorNotification } from '../utils';

describe('handleAddFaq', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleAddFaq();

    expect(createFaq).not.toHaveBeenCalled();
  });

  it('should add was-validated class when form is invalid', async () => {
    document.body.innerHTML = `
      <form id="pmf-add-faq-form" class="needs-validation">
        <input type="text" required value="" />
      </form>
      <button id="pmf-submit-faq">Submit</button>
    `;

    handleAddFaq();

    const button = document.getElementById('pmf-submit-faq') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      const form = document.querySelector('.needs-validation') as HTMLFormElement;
      expect(form.classList.contains('was-validated')).toBe(true);
    });

    expect(createFaq).not.toHaveBeenCalled();
  });

  it('should show success message and reset form on successful submission', async () => {
    document.body.innerHTML = `
      <form id="pmf-add-faq-form" class="needs-validation">
        <textarea name="question">Test question</textarea>
      </form>
      <button id="pmf-submit-faq">Submit</button>
      <div id="loader"></div>
      <div id="pmf-add-faq-response"></div>
    `;

    vi.mocked(createFaq).mockResolvedValue({ success: 'FAQ created successfully' });

    const form = document.getElementById('pmf-add-faq-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleAddFaq();

    const button = document.getElementById('pmf-submit-faq') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createFaq).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('FAQ created successfully');

    expect(resetSpy).toHaveBeenCalled();
  });

  it('should show error message on error response', async () => {
    document.body.innerHTML = `
      <form id="pmf-add-faq-form" class="needs-validation">
        <textarea name="question">Test</textarea>
      </form>
      <button id="pmf-submit-faq">Submit</button>
      <div id="loader"></div>
      <div id="pmf-add-faq-response"></div>
    `;

    vi.mocked(createFaq).mockResolvedValue({ error: 'Something went wrong' });

    handleAddFaq();

    const button = document.getElementById('pmf-submit-faq') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createFaq).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.innerText).toBe('Something went wrong');
  });
});

describe('handleShowFaq', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when bookmark toggle is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleShowFaq();

    expect(createBookmark).not.toHaveBeenCalled();
    expect(deleteBookmark).not.toHaveBeenCalled();
  });

  it('should return early when csrf or id attributes are missing', async () => {
    document.body.innerHTML = '<a id="pmf-bookmark-toggle" href="#">Bookmark</a>';

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(createBookmark).not.toHaveBeenCalled();
      expect(deleteBookmark).not.toHaveBeenCalled();
    });
  });

  it('should create bookmark and update UI on add action', async () => {
    document.body.innerHTML = `
      <a id="pmf-bookmark-toggle" href="#"
         data-pmf-csrf="token123"
         data-pmf-id="42"
         data-pmf-action="add">
        Add Bookmark
      </a>
      <i id="pmf-bookmark-icon" class="bi-bookmark"></i>
    `;

    vi.mocked(createBookmark).mockResolvedValue({
      success: 'Bookmark added',
      linkText: 'Remove Bookmark',
      csrfToken: 'new-token',
    });

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(createBookmark).toHaveBeenCalledWith('42', 'token123');
    });

    expect(pushNotification).toHaveBeenCalledWith('Bookmark added');

    const icon = document.getElementById('pmf-bookmark-icon') as HTMLElement;
    expect(icon.classList.contains('bi-bookmark-fill')).toBe(true);
    expect(icon.classList.contains('bi-bookmark')).toBe(false);

    expect(toggle.innerText).toBe('Remove Bookmark');
    expect(toggle.getAttribute('data-pmf-action')).toBe('remove');
    expect(toggle.getAttribute('data-pmf-csrf')).toBe('new-token');
  });

  it('should delete bookmark and update UI on remove action', async () => {
    document.body.innerHTML = `
      <a id="pmf-bookmark-toggle" href="#"
         data-pmf-csrf="token123"
         data-pmf-id="42"
         data-pmf-action="remove">
        Remove Bookmark
      </a>
      <i id="pmf-bookmark-icon" class="bi-bookmark-fill"></i>
    `;

    vi.mocked(deleteBookmark).mockResolvedValue({
      success: 'Bookmark removed',
      linkText: 'Add Bookmark',
      csrfToken: 'new-token-2',
    });

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(deleteBookmark).toHaveBeenCalledWith('42', 'token123');
    });

    expect(pushNotification).toHaveBeenCalledWith('Bookmark removed');

    const icon = document.getElementById('pmf-bookmark-icon') as HTMLElement;
    expect(icon.classList.contains('bi-bookmark')).toBe(true);
    expect(icon.classList.contains('bi-bookmark-fill')).toBe(false);

    expect(toggle.innerText).toBe('Add Bookmark');
    expect(toggle.getAttribute('data-pmf-action')).toBe('add');
    expect(toggle.getAttribute('data-pmf-csrf')).toBe('new-token-2');
  });

  it('should show error notification when create bookmark fails', async () => {
    document.body.innerHTML = `
      <a id="pmf-bookmark-toggle" href="#"
         data-pmf-csrf="token123"
         data-pmf-id="42"
         data-pmf-action="add">
        Add Bookmark
      </a>
    `;

    vi.mocked(createBookmark).mockResolvedValue({
      success: '',
      error: 'Not authorized',
    });

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(createBookmark).toHaveBeenCalled();
    });

    expect(pushErrorNotification).toHaveBeenCalledWith('Not authorized');
  });

  it('should show error notification when delete bookmark fails', async () => {
    document.body.innerHTML = `
      <a id="pmf-bookmark-toggle" href="#"
         data-pmf-csrf="token123"
         data-pmf-id="42"
         data-pmf-action="remove">
        Remove Bookmark
      </a>
    `;

    vi.mocked(deleteBookmark).mockResolvedValue({
      success: '',
      error: 'Failed to delete',
    });

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(deleteBookmark).toHaveBeenCalled();
    });

    expect(pushErrorNotification).toHaveBeenCalledWith('Failed to delete');
  });

  it('should show "Unknown error" when error field is missing on failed bookmark', async () => {
    document.body.innerHTML = `
      <a id="pmf-bookmark-toggle" href="#"
         data-pmf-csrf="token123"
         data-pmf-id="42"
         data-pmf-action="add">
        Add Bookmark
      </a>
    `;

    vi.mocked(createBookmark).mockResolvedValue({
      success: '',
    });

    handleShowFaq();

    const toggle = document.getElementById('pmf-bookmark-toggle') as HTMLElement;
    toggle.click();

    await vi.waitFor(() => {
      expect(pushErrorNotification).toHaveBeenCalledWith('Unknown error');
    });
  });
});

describe('handleShareLinkButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when copy button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleShareLinkButton();

    expect(pushNotification).not.toHaveBeenCalled();
  });

  it('should copy share link and show notification on click', async () => {
    document.body.innerHTML = `
      <input id="pmf-share-link" value="https://example.com/faq/42" />
      <button id="pmf-share-link-copy-button" data-pmf-message="Link copied!">Copy</button>
    `;

    const writeTextSpy = vi.fn().mockResolvedValue(undefined);
    Object.assign(navigator, {
      clipboard: { writeText: writeTextSpy },
    });

    handleShareLinkButton();

    const button = document.getElementById('pmf-share-link-copy-button') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(writeTextSpy).toHaveBeenCalledWith('https://example.com/faq/42');
    });

    expect(pushNotification).toHaveBeenCalledWith('Link copied!');
  });

  it('should not copy when share link input is missing', async () => {
    document.body.innerHTML = `
      <button id="pmf-share-link-copy-button" data-pmf-message="Link copied!">Copy</button>
    `;

    const writeTextSpy = vi.fn();
    Object.assign(navigator, {
      clipboard: { writeText: writeTextSpy },
    });

    handleShareLinkButton();

    const button = document.getElementById('pmf-share-link-copy-button') as HTMLButtonElement;
    button.click();

    // Give it a tick to process
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(writeTextSpy).not.toHaveBeenCalled();
    expect(pushNotification).not.toHaveBeenCalled();
  });

  it('should not copy when data-pmf-message attribute is missing', async () => {
    document.body.innerHTML = `
      <input id="pmf-share-link" value="https://example.com/faq/42" />
      <button id="pmf-share-link-copy-button">Copy</button>
    `;

    const writeTextSpy = vi.fn();
    Object.assign(navigator, {
      clipboard: { writeText: writeTextSpy },
    });

    handleShareLinkButton();

    const button = document.getElementById('pmf-share-link-copy-button') as HTMLButtonElement;
    button.click();

    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(writeTextSpy).not.toHaveBeenCalled();
    expect(pushNotification).not.toHaveBeenCalled();
  });
});
