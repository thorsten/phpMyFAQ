/**
 * phpMyFAQ Question Forms API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-02
 */

import { addElement, serialize } from '../utils';

export const handleQuestion = () => {
  const form = document.querySelector('#formValues');
  const loader = document.getElementById('loader');
  const message = document.getElementById('answers');
  const formData = new FormData(form);

  loader.classList.remove('d-none');
  fetch(`api.service.php?action=ask-question`, {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(serialize(formData)),
  })
    .then(async (response) => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok: ', { cause: { response } });
    })
    .then((response) => {
      loader.classList.add('d-none');
      const message = document.getElementById('answers');

      // Smart answers
      if (response.result) {
        const resultMessage = response.result;
        const form = document.getElementById('formValues');
        // Add smart answers
        message.insertAdjacentElement('afterend', addElement('div', { classList: '', innerHTML: resultMessage }));
        // Add hidden input
        form.insertAdjacentElement('afterbegin', addElement('input', { type: 'hidden', name: 'save', value: 1 }));
      }

      // Final result
      if (response.success) {
        const successMessage = response.success;
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-success', innerText: successMessage })
        );
      }
    })
    .catch(async (error) => {
      loader.classList.add('d-none');
      const message = document.getElementById('answers');
      const errorMessage = await error.cause.response.json();
      message.insertAdjacentElement(
        'afterend',
        addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
      );
    });
};
