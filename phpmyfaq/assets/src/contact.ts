/**
 * Contact form functionality
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

import { send } from './api';
import { addElement } from './utils';

export const handleContactForm = (): void => {
  const contactSubmit = document.getElementById('pmf-submit-contact') as HTMLElement;

  if (contactSubmit) {
    contactSubmit.addEventListener('click', async (event: PointerEvent): Promise<void> => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation') as HTMLFormElement;
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-contact-form') as HTMLFormElement;
        const loader = document.getElementById('loader') as HTMLElement;
        const formData = new FormData(form);

        try {
          const response = await send(formData);

          if (response.success) {
            loader.classList.add('d-none');
            const message = document.getElementById('pmf-contact-response') as HTMLElement;
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-success', innerText: response.success })
            );
            form.reset();
          }

          if (response.error) {
            loader.classList.add('d-none');
            const message = document.getElementById('pmf-contact-response') as HTMLElement;
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: response.error })
            );
          }
        } catch (error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-contact-response') as HTMLElement;
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: (error as Error).message })
          );
        }
      }
    });
  }
};
