/**
 * phpMyFAQ admin open questions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-04
 */

import { addElement, pushErrorNotification, serialize } from '../../../../assets/src/utils';
import { toggleQuestionVisibility } from '../api';

export const handleOpenQuestions = (): void => {
  const deleteButton = document.getElementById('pmf-delete-questions') as HTMLButtonElement | null;

  if (deleteButton) {
    deleteButton.addEventListener('click', (event: Event) => {
      event.preventDefault();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const form = document.querySelector('#phpmyfaq-open-questions') as HTMLFormElement;
      const questions = new FormData(form);

      responseMessage.innerHTML = '';
      fetch('./api/question/delete', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          data: serialize(questions),
        }),
      })
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          if (response.success) {
            responseMessage.append(
              addElement('div', { classList: 'alert alert-success', innerText: response.success })
            );
            const questionsToDelete = document.querySelectorAll('tr td input:checked') as NodeListOf<HTMLInputElement>;
            questionsToDelete.forEach((toDelete) => {
              (toDelete.parentNode?.parentNode?.parentNode as HTMLElement | null)?.remove();
            });
          } else {
            responseMessage.append(addElement('div', { classList: 'alert alert-danger', innerText: response.error }));
          }
        })
        .catch(async (error) => {
          const errorMessage = await error.cause.response.json();
          responseMessage.append(addElement('div', { classList: 'alert alert-danger', innerText: errorMessage }));
        });
    });
  }
};

export const handleToggleVisibility = (): void => {
  const toggleVisibility = document.querySelectorAll('.pmf-toggle-visibility') as NodeListOf<HTMLElement>;

  if (toggleVisibility) {
    toggleVisibility.forEach((element) => {
      element.addEventListener('click', async (event: Event) => {
        event.preventDefault();

        const questionId = element.getAttribute('data-pmf-question-id') as string;
        const visibility = element.getAttribute('data-pmf-visibility') as string;
        const csrfToken = element.getAttribute('data-pmf-csrf') as string;

        const response = await toggleQuestionVisibility(questionId, visibility === 'true', csrfToken);

        if (response?.success) {
          element.innerText = response.success;
        } else {
          pushErrorNotification(response?.error ?? 'An error occurred');
        }
      });
    });
  }
};
