/**
 * phpMyFAQ Question Forms API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-02
 */

import { addElement } from '../utils';
import { createQuestion } from '../api';

export const handleQuestion = () => {
  const questionSubmit = document.getElementById('pmf-submit-question');

  if (questionSubmit) {
    questionSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-question-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await createQuestion(formData);

        // Smart answers
        if (response.result) {
          const resultMessage = response.result;
          const message = document.getElementById('pmf-question-response');
          const hints = document.getElementsByClassName('hint-search-suggestion');

          Array.from(hints).forEach((hint) => {
            hint.classList.remove('d-none');
          });

          // Add smart answers
          message.insertAdjacentElement('afterend', addElement('div', { classList: '', innerHTML: resultMessage }));
          // Add hidden input
          form.insertAdjacentElement('afterbegin', addElement('input', { type: 'hidden', name: 'save', value: 1 }));
          form.insertAdjacentElement(
            'afterbegin',
            addElement('input', { type: 'hidden', name: 'store', value: 'now' })
          );
        }

        // Final result
        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-question-response');

          message.insertAdjacentElement(
            'beforeend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-question-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};
