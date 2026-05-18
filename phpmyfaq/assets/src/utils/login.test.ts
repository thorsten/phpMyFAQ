import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { handleLoginForm, handleSessionTimeoutMessage } from './login';

describe('handleLoginForm - Caps Lock warning', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <form class="pmf-login-form">
        <input type="password" id="faqpassword">
        <div id="caps-lock-warning" class="d-none"></div>
        <button type="submit">Login</button>
      </form>
    `;
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('shows the warning when Caps Lock is on', () => {
    handleLoginForm();

    const password = document.getElementById('faqpassword') as HTMLInputElement;
    const warning = document.getElementById('caps-lock-warning') as HTMLElement;

    const event = new KeyboardEvent('keyup', { key: 'a' });
    Object.defineProperty(event, 'getModifierState', { value: () => true });
    password.dispatchEvent(event);

    expect(warning.classList.contains('d-none')).toBe(false);
  });

  test('hides the warning when Caps Lock is off', () => {
    handleLoginForm();

    const password = document.getElementById('faqpassword') as HTMLInputElement;
    const warning = document.getElementById('caps-lock-warning') as HTMLElement;

    const event = new KeyboardEvent('keyup', { key: 'a' });
    Object.defineProperty(event, 'getModifierState', { value: () => false });
    password.dispatchEvent(event);

    expect(warning.classList.contains('d-none')).toBe(true);
  });

  test('hides the warning when the password field loses focus', () => {
    handleLoginForm();

    const password = document.getElementById('faqpassword') as HTMLInputElement;
    const warning = document.getElementById('caps-lock-warning') as HTMLElement;

    warning.classList.remove('d-none');
    password.dispatchEvent(new Event('blur'));

    expect(warning.classList.contains('d-none')).toBe(true);
  });
});

describe('handleLoginForm - submit state', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <form class="pmf-login-form">
        <input type="text" id="faqusername" value="admin">
        <button type="submit" data-pmf-loading-text="Signing in…">Login</button>
      </form>
    `;
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('disables the submit button and shows a spinner on submit', () => {
    handleLoginForm();

    const form = document.querySelector('.pmf-login-form') as HTMLFormElement;
    const button = form.querySelector('button[type="submit"]') as HTMLButtonElement;

    const event = new Event('submit', { cancelable: true });
    form.dispatchEvent(event);

    expect(button.disabled).toBe(true);
    expect(button.innerHTML).toContain('spinner-border');
    expect(button.innerHTML).toContain('Signing in…');
  });
});

describe('handleSessionTimeoutMessage', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="session-timeout-message" class="d-none">
        <span id="session-timeout-text"></span>
      </div>
    `;
    sessionStorage.clear();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    sessionStorage.clear();
  });

  test('renders a stored message and clears it from sessionStorage', () => {
    sessionStorage.setItem('loginMessage', 'Your session expired.');

    handleSessionTimeoutMessage();

    const message = document.getElementById('session-timeout-message') as HTMLElement;
    const text = document.getElementById('session-timeout-text') as HTMLElement;

    expect(message.classList.contains('d-none')).toBe(false);
    expect(text.textContent).toBe('Your session expired.');
    expect(sessionStorage.getItem('loginMessage')).toBeNull();
  });

  test('does nothing when no message is stored', () => {
    handleSessionTimeoutMessage();

    const message = document.getElementById('session-timeout-message') as HTMLElement;
    expect(message.classList.contains('d-none')).toBe(true);
  });
});
