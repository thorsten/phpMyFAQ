/**
 * phpMyFAQ Forms API
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
 * @since     2023-01-06
 */

import { addElement, redirect, serialize } from '../utils';

export const saveFormData = (action) => {
  const form = document.querySelector('#formValues');
  const loader = document.getElementById('loader');
  const formData = new FormData(form);

  loader.classList.remove('d-none');
  fetch(`api.service.php?action=${action}`, {
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
      const message = document.getElementById('faqs');
      message.insertAdjacentElement(
        'afterend',
        addElement('div', { classList: 'alert alert-success', innerText: response.success })
      );

      if (response.link) {
        message.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-info', innerText: response.info })
        );
        window.setTimeout(() => {
          redirect(response.link);
        }, 5000);
      }
    })
    .catch(async (error) => {
      loader.classList.add('d-none');
      const message = document.getElementById('faqs');
      const errorMessage = await error.cause.response.json();
      message.insertAdjacentElement(
        'afterend',
        addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
      );
    });
};
