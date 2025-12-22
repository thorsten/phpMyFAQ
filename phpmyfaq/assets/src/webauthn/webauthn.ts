/**
 * Handle WebAuthn flows
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */
import { webauthnRegister } from './register';
import { webauthnAuthenticate } from './authenticate';
import { AuthenticatorResponse } from '../interfaces';

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
        const response = await fetch('./api/webauthn/prepare', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ username: registerUsername }),
        });

        if (response.ok) {
          const jsonResponse = await response.json();

          await webauthnRegister(
            jsonResponse.challenge,
            async (success: boolean, info: string | AuthenticatorResponse) => {
              if (success) {
                try {
                  const response = await fetch('./api/webauthn/register', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ register: info }),
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
            }
          );
        } else {
          errorMessage.textContent = "Couldn't initiate registration.";
          errorMessage.classList.remove('d-none');
        }
      } catch (error: unknown) {
        errorMessage.textContent = error instanceof Error ? error.message : String(error);
        errorMessage.classList.remove('d-none');
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

          await webauthnAuthenticate(jsonResponse, async (success: boolean, info: string | AuthenticatorResponse) => {
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
