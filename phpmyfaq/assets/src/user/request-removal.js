/**
 * User requests removal functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

import { requestUserRemoval } from '../api';
import { addElement } from '../utils';

export const handleRequestRemoval = () => {
  const requestRemovalSubmit = document.getElementById('pmf-submit-request-removal');

  if (requestRemovalSubmit) {
    requestRemovalSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-request-removal-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await requestUserRemoval(formData);

        if (response.success) {
          console.log(response.success);
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-request-removal-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-request-removal-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};
