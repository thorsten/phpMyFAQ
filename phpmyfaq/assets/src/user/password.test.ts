import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../api', () => ({
  updateUserPassword: vi.fn(),
}));

vi.mock('../utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
}));

import { handleUserPassword } from './password';
import { updateUserPassword } from '../api';

describe('handleUserPassword', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when submit button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleUserPassword();

    expect(updateUserPassword).not.toHaveBeenCalled();
  });

  it('should show success message and reset form on successful password change', async () => {
    document.body.innerHTML = `
      <form id="pmf-password-form">
        <input name="password" value="newpass123" />
      </form>
      <button id="pmf-submit-password">Change</button>
      <div id="loader"></div>
      <div id="pmf-password-response"></div>
    `;

    vi.mocked(updateUserPassword).mockResolvedValue({ success: 'Password updated successfully' });

    const form = document.getElementById('pmf-password-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleUserPassword();

    const button = document.getElementById('pmf-submit-password') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(updateUserPassword).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const successAlert = document.querySelector('.alert-success') as HTMLElement | null;
    expect(successAlert).not.toBeNull();
    expect(successAlert?.innerText).toBe('Password updated successfully');

    expect(resetSpy).toHaveBeenCalled();
  });

  it('should show error message on failed password change', async () => {
    document.body.innerHTML = `
      <form id="pmf-password-form">
        <input name="password" value="weak" />
      </form>
      <button id="pmf-submit-password">Change</button>
      <div id="loader"></div>
      <div id="pmf-password-response"></div>
    `;

    vi.mocked(updateUserPassword).mockResolvedValue({ error: 'Password too short' });

    handleUserPassword();

    const button = document.getElementById('pmf-submit-password') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(updateUserPassword).toHaveBeenCalled();
    });

    const loader = document.getElementById('loader') as HTMLElement;
    expect(loader.classList.contains('d-none')).toBe(true);

    const errorAlert = document.querySelector('.alert-danger') as HTMLElement | null;
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.innerText).toBe('Password too short');
  });

  it('should not reset form on error', async () => {
    document.body.innerHTML = `
      <form id="pmf-password-form">
        <input name="password" value="test" />
      </form>
      <button id="pmf-submit-password">Change</button>
      <div id="loader"></div>
      <div id="pmf-password-response"></div>
    `;

    vi.mocked(updateUserPassword).mockResolvedValue({ error: 'Failed' });

    const form = document.getElementById('pmf-password-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleUserPassword();

    const button = document.getElementById('pmf-submit-password') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(updateUserPassword).toHaveBeenCalled();
    });

    expect(resetSpy).not.toHaveBeenCalled();
  });

  it('should send form data to updateUserPassword', async () => {
    document.body.innerHTML = `
      <form id="pmf-password-form">
        <input name="current_password" value="old123" />
        <input name="new_password" value="new456" />
      </form>
      <button id="pmf-submit-password">Change</button>
      <div id="loader"></div>
      <div id="pmf-password-response"></div>
    `;

    vi.mocked(updateUserPassword).mockResolvedValue({ success: 'Done' });

    handleUserPassword();

    const button = document.getElementById('pmf-submit-password') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(updateUserPassword).toHaveBeenCalledTimes(1);
    });

    const passedData = vi.mocked(updateUserPassword).mock.calls[0][0] as FormData;
    expect(passedData).toBeInstanceOf(FormData);
    expect(passedData.get('current_password')).toBe('old123');
    expect(passedData.get('new_password')).toBe('new456');
  });
});
