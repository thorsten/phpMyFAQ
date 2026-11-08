/**
 * Handle WebAuthn flows
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */
import { webauthnRegister } from './register';
import { webauthnAuthenticate } from './authenticate';
import { revokePasskey } from '../api';
import { AuthenticatorResponse, Callback } from '../interfaces';
import { pushErrorNotification, pushNotification } from '../utils';

/**
 * Adding a passkey to an existing account needs proof of ownership beyond the session: the
 * server tells the form which kind through data-pmf-step-up. A password account sends its
 * current password; a passkey account first signs a login challenge with one of its existing
 * passkeys and sends that assertion along.
 */
const collectStepUp = async (registerForm: HTMLFormElement, username: string): Promise<Record<string, unknown>> => {
  const stepUp = registerForm.dataset.pmfStepUp ?? 'none';

  if (stepUp === 'password') {
    const passwordField = document.getElementById('pmf-webauthn-current-password') as HTMLInputElement | null;
    return { 'current-password': passwordField?.value ?? '' };
  }

  if (stepUp !== 'passkey') {
    return {};
  }

  const response = await fetch('./api/webauthn/prepare-login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error?.error ?? "Couldn't initiate registration.");
  }

  const loginChallenge = await response.json();
  const assertion = await new Promise<AuthenticatorResponse>((resolve, reject) => {
    void webauthnAuthenticate(loginChallenge, (success: boolean, info: Parameters<Callback>[1]) => {
      if (success) {
        resolve(info as AuthenticatorResponse);
      } else {
        reject(new Error(info as string));
      }
    });
  });

  return { assertion };
};

export const handleWebAuthn = (): void => {
  const registerForm = document.getElementById('pmf-webauthn-form') as HTMLFormElement | null;
  const loginForm = document.getElementById('pmf-webauthn-login-form') as HTMLFormElement | null;
  const errorMessage = document.getElementById('pmf-webauthn-error') as HTMLElement;
  const successMessage = document.getElementById('pmf-webauthn-success') as HTMLElement;

  if (registerForm) {
    registerForm.addEventListener('submit', async (event: Event) => {
      event.preventDefault();

      errorMessage.textContent = '';

      try {
        const registerUsername = (document.querySelector('[id=webauthn]') as HTMLInputElement).value;
        const csrfToken = (document.getElementById('pmf-csrf-token-webauthn') as HTMLInputElement)?.value ?? '';
        const stepUp = await collectStepUp(registerForm, registerUsername);
        const response = await fetch('./api/webauthn/prepare', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ username: registerUsername, 'pmf-csrf-token': csrfToken, ...stepUp }),
        });

        if (response.ok) {
          const jsonResponse = await response.json();

          await webauthnRegister(jsonResponse.challenge, async (success: boolean, info: Parameters<Callback>[1]) => {
            if (success) {
              try {
                const response = await fetch('./api/webauthn/register', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({ register: info, 'pmf-csrf-token': csrfToken }),
                });

                const jsonResponse = await response.json();
                if (jsonResponse.success === 'ok') {
                  successMessage.classList.remove('d-none');
                  successMessage.textContent = jsonResponse.message;
                  errorMessage.classList.add('d-none');
                } else {
                  errorMessage.textContent = 'Registration failed.';
                  errorMessage.classList.remove('d-none');
                }
              } catch (error: unknown) {
                errorMessage.textContent = `Registration failed: ${error instanceof Error ? error.message : String(error)}`;
                errorMessage.classList.remove('d-none');
              }
            } else {
              errorMessage.textContent = info as string;
              errorMessage.classList.remove('d-none');
            }
          });
        } else {
          // A failed step-up (wrong password, lockout) is reported by the server; show it so the
          // owner knows what to correct instead of a generic failure.
          const error = await response.json();
          errorMessage.textContent = error?.error ?? "Couldn't initiate registration.";
          errorMessage.classList.remove('d-none');
        }
      } catch (error: unknown) {
        errorMessage.textContent = error instanceof Error ? error.message : String(error);
        errorMessage.classList.remove('d-none');
      }
    });
  }

  // Removing one of the registered passkeys from the UCP list
  const passkeyList = document.getElementById('pmf-passkey-list');
  if (passkeyList) {
    passkeyList.addEventListener('click', async (event: Event) => {
      const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-pmf-passkey-revoke]');
      if (!button) {
        return;
      }

      event.preventDefault();
      const csrfToken = (document.getElementById('pmf-csrf-token-webauthn') as HTMLInputElement | null)?.value ?? '';
      const credentialId = button.dataset.pmfPasskeyRevoke ?? '';

      try {
        const response = await revokePasskey(csrfToken, credentialId);
        if (response.success) {
          pushNotification(response.success);
          button.closest('li')?.remove();
          if (passkeyList.querySelector('[data-pmf-passkey-id]') === null) {
            const empty = document.createElement('li');
            empty.className = 'list-group-item text-muted';
            empty.textContent = passkeyList.dataset.pmfEmptyText ?? '';
            passkeyList.appendChild(empty);
          }
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      } catch (error: unknown) {
        pushErrorNotification(error instanceof Error ? error.message : String(error));
      }
    });
  }

  if (loginForm) {
    loginForm.addEventListener('submit', async (ev: Event) => {
      ev.preventDefault();

      const loginUsername = (document.querySelector('[name=faqusername]') as HTMLInputElement).value;
      errorMessage.textContent = '';
      errorMessage.classList.add('d-none');

      try {
        const response = await fetch('./api/webauthn/prepare-login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ username: loginUsername }),
        });

        if (response.ok) {
          const jsonResponse = await response.json();

          await webauthnAuthenticate(jsonResponse, async (success: boolean, info: Parameters<Callback>[1]) => {
            if (success) {
              try {
                const response = await fetch('./api/webauthn/login', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({ login: info, username: loginUsername }),
                });

                if (response.ok) {
                  const result = await response.json();
                  window.location.href = result.redirect;
                } else {
                  const result = await response.json();
                  errorMessage.textContent = result.error;
                  errorMessage.classList.remove('d-none');
                }
              } catch (error: unknown) {
                errorMessage.textContent = error instanceof Error ? error.message : String(error);
                errorMessage.classList.remove('d-none');
              }
            } else {
              errorMessage.textContent = info as string;
              errorMessage.classList.remove('d-none');
            }
          });
        } else {
          const error = await response.json();
          errorMessage.textContent = error.error;
          errorMessage.classList.remove('d-none');
        }
      } catch (error: unknown) {
        errorMessage.textContent = error instanceof Error ? error.message : String(error);
        errorMessage.classList.remove('d-none');
      }
    });
  }
};
