/**
 * Login form UX helpers: Caps Lock warning, submit loading state,
 * and session timeout messaging.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-05-18
 */

/**
 * Shows a Caps Lock warning while the password field is focused.
 */
const handleCapsLockWarning = (form: HTMLFormElement): void => {
  const password: HTMLInputElement | null = form.querySelector('#faqpassword');
  const warning: HTMLElement | null = form.querySelector('#caps-lock-warning');

  if (!password || !warning) {
    return;
  }

  const updateWarning = (event: KeyboardEvent): void => {
    const capsLockOn: boolean = event.getModifierState('CapsLock');
    warning.classList.toggle('d-none', !capsLockOn);
  };

  password.addEventListener('keydown', updateWarning);
  password.addEventListener('keyup', updateWarning);
  password.addEventListener('blur', (): void => {
    warning.classList.add('d-none');
  });
};

/**
 * Disables the submit button and shows a spinner to prevent double submits.
 */
const handleSubmitState = (form: HTMLFormElement): void => {
  const submitButton: HTMLButtonElement | null = form.querySelector('button[type="submit"]');

  if (!submitButton) {
    return;
  }

  form.addEventListener('submit', (): void => {
    if (!form.checkValidity()) {
      return;
    }

    submitButton.disabled = true;
    const loadingText: string | null = submitButton.getAttribute('data-pmf-loading-text');
    submitButton.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
      (loadingText ?? submitButton.textContent ?? '');
  });
};

/**
 * Restores a session timeout message stored before a redirect to the login page.
 */
export const handleSessionTimeoutMessage = (): void => {
  const message: string | null = sessionStorage.getItem('loginMessage');

  if (!message) {
    return;
  }

  const messageElement: HTMLElement | null = document.getElementById('session-timeout-message');
  const textElement: HTMLElement | null = document.getElementById('session-timeout-text');

  if (messageElement && textElement) {
    textElement.textContent = message;
    messageElement.classList.remove('d-none');
  }

  sessionStorage.removeItem('loginMessage');
};

/**
 * Wires up all login form enhancements.
 */
export const handleLoginForm = (): void => {
  const form: HTMLFormElement | null = document.querySelector('.pmf-login-form');

  if (!form) {
    return;
  }

  handleCapsLockWarning(form);
  handleSubmitState(form);
  handleSessionTimeoutMessage();
};
