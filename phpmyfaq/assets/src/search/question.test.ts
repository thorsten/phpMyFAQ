import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    if (props.innerHTML) el.innerHTML = props.innerHTML;
    if (props.type) el.setAttribute('type', props.type);
    if (props.name) el.setAttribute('name', props.name);
    if (props.value) el.setAttribute('value', props.value);
    return el;
  }),
}));

vi.mock('../api', () => ({
  createQuestion: vi.fn(),
}));

import { handleQuestion } from './question';
import { createQuestion } from '../api';

describe('handleQuestion', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleQuestion();

    expect(createQuestion).not.toHaveBeenCalled();
  });

  it('should add was-validated class when form is invalid', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <input type="text" required value="" />
      </form>
      <button id="pmf-submit-question">Submit</button>
    `;

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      const form = document.querySelector('.needs-validation') as HTMLFormElement;
      expect(form.classList.contains('was-validated')).toBe(true);
    });

    expect(createQuestion).not.toHaveBeenCalled();
  });

  it('should show smart answers when result is returned', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <textarea name="question">How to install?</textarea>
      </form>
      <button id="pmf-submit-question">Submit</button>
      <div id="loader"></div>
      <div id="pmf-question-response"></div>
      <div class="hint-search-suggestion d-none">Hint 1</div>
      <div class="hint-search-suggestion d-none">Hint 2</div>
    `;

    vi.mocked(createQuestion).mockResolvedValue({
      result: '<a href="/faq/1">Similar FAQ</a>',
    });

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createQuestion).toHaveBeenCalled();
    });

    // Hints should be visible
    const hints = document.getElementsByClassName('hint-search-suggestion');
    Array.from(hints).forEach((hint) => {
      expect(hint.classList.contains('d-none')).toBe(false);
    });

    // Smart answer should be inserted
    const response = document.getElementById('pmf-question-response') as HTMLElement;
    expect(response.nextElementSibling?.innerHTML).toContain('Similar FAQ');

    // Hidden inputs should be added to the form
    const form = document.getElementById('pmf-question-form') as HTMLFormElement;
    const saveInput = form.querySelector('input[name="save"]') as HTMLInputElement;
    const storeInput = form.querySelector('input[name="store"]') as HTMLInputElement;
    expect(saveInput).not.toBeNull();
    expect(saveInput.getAttribute('value')).toBe('1');
    expect(storeInput).not.toBeNull();
    expect(storeInput.getAttribute('value')).toBe('now');
  });

  it('should show success message and reset form on success', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <textarea name="question">Test question</textarea>
      </form>
      <button id="pmf-submit-question">Submit</button>
      <div id="loader"></div>
      <div id="pmf-question-response"></div>
    `;

    vi.mocked(createQuestion).mockResolvedValue({
      success: 'Question submitted successfully',
    });

    const form = document.getElementById('pmf-question-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createQuestion).toHaveBeenCalled();
    });

    // Loader should be hidden
    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    // Success alert should be present
    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('Question submitted successfully');

    // Form should be reset
    expect(resetSpy).toHaveBeenCalled();
  });

  it('should show error message on error response', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <textarea name="question">Test</textarea>
      </form>
      <button id="pmf-submit-question">Submit</button>
      <div id="loader"></div>
      <div id="pmf-question-response"></div>
    `;

    vi.mocked(createQuestion).mockResolvedValue({
      error: 'Something went wrong',
    });

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createQuestion).toHaveBeenCalled();
    });

    // Loader should be hidden
    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    // Error alert should be present
    const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.innerText).toBe('Something went wrong');
  });

  it('should handle smart answers followed by success', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <textarea name="question">How to?</textarea>
      </form>
      <button id="pmf-submit-question">Submit</button>
      <div id="loader"></div>
      <div id="pmf-question-response"></div>
    `;

    vi.mocked(createQuestion).mockResolvedValue({
      result: '<p>Try these FAQs</p>',
      success: 'Question saved',
    });

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createQuestion).toHaveBeenCalled();
    });

    // Both smart answer and success should be present
    const response = document.getElementById('pmf-question-response') as HTMLElement;
    expect(response.nextElementSibling?.innerHTML).toContain('Try these FAQs');

    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('Question saved');
  });

  it('should not show hints when no result is returned', async () => {
    document.body.innerHTML = `
      <form id="pmf-question-form" class="needs-validation">
        <textarea name="question">Test</textarea>
      </form>
      <button id="pmf-submit-question">Submit</button>
      <div id="loader"></div>
      <div id="pmf-question-response"></div>
      <div class="hint-search-suggestion d-none">Hint</div>
    `;

    vi.mocked(createQuestion).mockResolvedValue({
      success: 'Saved',
    });

    handleQuestion();

    const button = document.getElementById('pmf-submit-question') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createQuestion).toHaveBeenCalled();
    });

    // Hints should remain hidden
    const hints = document.getElementsByClassName('hint-search-suggestion');
    Array.from(hints).forEach((hint) => {
      expect(hint.classList.contains('d-none')).toBe(true);
    });
  });
});
