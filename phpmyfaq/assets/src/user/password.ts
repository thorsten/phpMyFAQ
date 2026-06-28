/**
 * User request password functionality
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
 * @since     2024-03-03
 */

import { resetUserPassword, updateUserPassword } from '../api';
import { addElement } from '../utils';

export const handleUserPassword = () => {
  const changePasswordSubmit = document.getElementById('pmf-submit-password') as HTMLButtonElement | null;

  if (changePasswordSubmit) {
    changePasswordSubmit.addEventListener('click', async (event) => {
      event.preventDefault();

      const form = document.querySelector('#pmf-password-form') as HTMLFormElement;
      const loader = document.getElementById('loader') as HTMLElement;
      const formData = new FormData(form);
      const message = document.getElementById('pmf-password-response') as HTMLElement;

      try {
        const response = await updateUserPassword(formData);

        if (response.success) {
          loader.classList.add('d-none');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      } catch (error) {
        loader.classList.add('d-none');
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: (error as Error).message })
        );
      }
    });
  }
};

export const handleResetUserPassword = () => {
  const submitButton = document.getElementById('pmf-submit-resetpw') as HTMLButtonElement | null;

  if (submitButton) {
    submitButton.addEventListener('click', async (event) => {
      event.preventDefault();

      const form = document.querySelector('#pmf-resetpw-form') as HTMLFormElement;
      const loader = document.getElementById('loader') as HTMLElement;
      const formData = new FormData(form);
      const message = document.getElementById('pmf-resetpw-response') as HTMLElement;

      try {
        const response = await resetUserPassword(formData);

        if (response.success) {
          loader.classList.add('d-none');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      } catch (error) {
        loader.classList.add('d-none');
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: (error as Error).message })
        );
      }
    });
  }
};
