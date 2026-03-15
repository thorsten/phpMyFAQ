import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../api', () => ({
  requestUserRemoval: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((_tag: string, props: Record<string, string>) => {
    const el = document.createElement(_tag);
    if (props.classList) {
      el.className = props.classList;
    }
    if (props.innerText) {
      el.innerText = props.innerText;
    }
    return el;
  }),
}));

import { requestUserRemoval } from '../api';
import { handleRequestRemoval } from './request-removal';

describe('handleRequestRemoval', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('does nothing when the submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleRequestRemoval();

    expect(requestUserRemoval).not.toHaveBeenCalled();
  });

  it('adds was-validated when the form is invalid', async () => {
    document.body.innerHTML = `
      <form id="pmf-request-removal-form" class="needs-validation">
        <input type="email" required value="" />
      </form>
      <button id="pmf-submit-request-removal">Submit</button>
    `;

    handleRequestRemoval();

    const button = document.getElementById('pmf-submit-request-removal') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      const form = document.querySelector('.needs-validation') as HTMLFormElement;
      expect(form.classList.contains('was-validated')).toBe(true);
    });

    expect(requestUserRemoval).not.toHaveBeenCalled();
  });

  it('shows a success message and resets the form on successful submission', async () => {
    document.body.innerHTML = `
      <form id="pmf-request-removal-form" class="needs-validation">
        <input type="email" name="email" value="user@example.com" />
      </form>
      <button id="pmf-submit-request-removal">Submit</button>
      <div id="loader"></div>
      <div id="pmf-request-removal-response"></div>
    `;

    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.mocked(requestUserRemoval).mockResolvedValue({ success: 'Removal requested' });

    const form = document.getElementById('pmf-request-removal-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleRequestRemoval();

    const button = document.getElementById('pmf-submit-request-removal') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(requestUserRemoval).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('Removal requested');
    expect(resetSpy).toHaveBeenCalled();
  });

  it('shows an error message on failed submission', async () => {
    document.body.innerHTML = `
      <form id="pmf-request-removal-form" class="needs-validation">
        <input type="email" name="email" value="user@example.com" />
      </form>
      <button id="pmf-submit-request-removal">Submit</button>
      <div id="loader"></div>
      <div id="pmf-request-removal-response"></div>
    `;

    vi.mocked(requestUserRemoval).mockResolvedValue({ error: 'Removal failed' });

    handleRequestRemoval();

    const button = document.getElementById('pmf-submit-request-removal') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(requestUserRemoval).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.innerText).toBe('Removal failed');
  });
});
