/**
 * Comment handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-12
 */

import { addElement, serialize } from '../../../../assets/src/utils';

export const handleDeleteComments = (): void => {
  const deleteFaqComments = document.getElementById('pmf-button-delete-faq-comments');
  const deleteNewsComments = document.getElementById('pmf-button-delete-news-comments');

  if (deleteFaqComments) {
    deleteFaqComments.addEventListener('click', () => {
      deleteComments('faq');
    });
  }

  if (deleteNewsComments) {
    deleteNewsComments.addEventListener('click', () => {
      deleteComments('news');
    });
  }
};

const deleteComments = (type: 'faq' | 'news'): void => {
  const responseMessage = document.getElementById('returnMessage') as HTMLElement;
  const form = document.getElementById(`pmf-comments-selected-${type}`) as HTMLFormElement;
  const comments = new FormData(form);

  fetch(`${window.location.pathname}api/content/comments`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      type: type,
      data: serialize(comments),
    }),
  })
    .then(async (response) => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then((response) => {
      if (response.success) {
        const commentsToDelete = document.querySelectorAll('tr td input:checked');
        commentsToDelete.forEach((toDelete) => {
          const row = toDelete.parentNode?.parentNode?.parentNode;
          if (row instanceof HTMLElement) {
            row.remove();
          }
        });
      } else {
        responseMessage.append(addElement('div', { className: 'alert alert-danger', innerText: response.error }));
      }
    })
    .catch((error: unknown) => {
      const errorMsg = error instanceof Error ? error.message : 'An error occurred while deleting comments';
      responseMessage.append(addElement('div', { className: 'alert alert-danger', innerText: errorMsg }));
    });
};
