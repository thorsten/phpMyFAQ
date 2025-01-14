/**
 * Contact form functionality
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
 * @since     2024-03-10
 */

import { send } from './api';
import { addElement } from './utils';

export const handleContactForm = () => {
  const contactSubmit = document.getElementById('pmf-submit-contact');

  if (contactSubmit) {
    contactSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-contact-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await send(formData);

        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-contact-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-contact-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};
