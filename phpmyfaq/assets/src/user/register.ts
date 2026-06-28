/**
 * User registration functionality
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
 * @since     2024-03-10
 */

import { ApiResponse } from '../interfaces';
import { addElement } from '../utils';
import { register } from '../api';

export const handleRegister = () => {
  const registerSubmit = document.getElementById('pmf-submit-register') as HTMLButtonElement | null;

  if (registerSubmit) {
    registerSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation') as HTMLFormElement;
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-register-form') as HTMLFormElement;
        const loader = document.getElementById('loader') as HTMLElement;
        const message = document.getElementById('pmf-register-response') as HTMLElement;
        const formData = new FormData(form);

        try {
          const response: ApiResponse = await register(formData);

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
      }
    });
  }
};
