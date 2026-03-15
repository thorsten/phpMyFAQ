import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../api', () => ({
  register: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
}));

import { handleRegister } from './register';
import { register } from '../api';

describe('handleRegister', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleRegister();

    expect(register).not.toHaveBeenCalled();
  });

  it('should add was-validated class when form is invalid', async () => {
    document.body.innerHTML = `
      <form id="pmf-register-form" class="needs-validation">
        <input type="text" required value="" />
      </form>
      <button id="pmf-submit-register">Register</button>
    `;

    handleRegister();

    const button = document.getElementById('pmf-submit-register') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      const form = document.querySelector('.needs-validation') as HTMLFormElement;
      expect(form.classList.contains('was-validated')).toBe(true);
    });

    expect(register).not.toHaveBeenCalled();
  });

  it('should show success message and reset form on successful registration', async () => {
    document.body.innerHTML = `
      <form id="pmf-register-form" class="needs-validation">
        <input name="username" value="newuser" />
        <input name="email" value="user@example.com" />
      </form>
      <button id="pmf-submit-register">Register</button>
      <div id="loader"></div>
      <div id="pmf-register-response"></div>
    `;

    vi.mocked(register).mockResolvedValue({ success: 'Registration successful' });

    const form = document.getElementById('pmf-register-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleRegister();

    const button = document.getElementById('pmf-submit-register') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(register).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('Registration successful');

    expect(resetSpy).toHaveBeenCalled();
  });

  it('should show error message on failed registration', async () => {
    document.body.innerHTML = `
      <form id="pmf-register-form" class="needs-validation">
        <input name="username" value="taken" />
      </form>
      <button id="pmf-submit-register">Register</button>
      <div id="loader"></div>
      <div id="pmf-register-response"></div>
    `;

    vi.mocked(register).mockResolvedValue({ error: 'Username already exists' });

    handleRegister();

    const button = document.getElementById('pmf-submit-register') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(register).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.innerText).toBe('Username already exists');
  });

  it('should not reset form on error', async () => {
    document.body.innerHTML = `
      <form id="pmf-register-form" class="needs-validation">
        <input name="username" value="test" />
      </form>
      <button id="pmf-submit-register">Register</button>
      <div id="loader"></div>
      <div id="pmf-register-response"></div>
    `;

    vi.mocked(register).mockResolvedValue({ error: 'Failed' });

    const form = document.getElementById('pmf-register-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleRegister();

    const button = document.getElementById('pmf-submit-register') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(register).toHaveBeenCalled();
    });

    expect(resetSpy).not.toHaveBeenCalled();
  });

  it('should send form data to register API', async () => {
    document.body.innerHTML = `
      <form id="pmf-register-form" class="needs-validation">
        <input name="username" value="newuser" />
        <input name="email" value="new@example.com" />
      </form>
      <button id="pmf-submit-register">Register</button>
      <div id="loader"></div>
      <div id="pmf-register-response"></div>
    `;

    vi.mocked(register).mockResolvedValue({ success: 'Done' });

    handleRegister();

    const button = document.getElementById('pmf-submit-register') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(register).toHaveBeenCalledTimes(1);
    });

    const passedData = vi.mocked(register).mock.calls[0][0] as FormData;
    expect(passedData).toBeInstanceOf(FormData);
    expect(passedData.get('username')).toBe('newuser');
    expect(passedData.get('email')).toBe('new@example.com');
  });
});
