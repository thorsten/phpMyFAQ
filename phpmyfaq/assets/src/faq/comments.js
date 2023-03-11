/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-01-11
 */

import { addElement, serialize } from '../utils';

export const handleSaveComment = () => {
  const saveButton = document.getElementById('pmf-button-save-comment');
  const modal = document.getElementById('pmf-modal-add-comment');
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');

  if (saveButton) {
    saveButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const form = document.querySelector('#pmf-add-comment-form');
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
      } else {
        const comments = new FormData(form);

        fetch('api.service.php?action=add-comment', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(serialize(comments)),
        })
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            if (response.success) {
              const message = document.getElementById('pmf-comment-add-success');
              message.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-success', innerText: response.success })
              );
              modal.style.display = 'none';
              modal.classList.remove('show');
              modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
            } else {
              const element = document.getElementById('pmf-add-comment-error');
              element.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-danger', innerText: response.error })
              );
            }
          })
          .catch(async (error) => {
            const element = document.getElementById('pmf-add-comment-error');
            const errorMessage = await error.cause.response.json();
            element.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
            );
          });
      }
    });
  }
};

export const handleComments = () => {
  const showMoreComments = document.querySelectorAll('.pmf-comments-show-more');

  if (showMoreComments) {
    showMoreComments.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const commentId = event.target.getAttribute('data-comment-id');
        showLongComment(commentId);
      });
    });
  }
};

const showLongComment = (id) => {
  document.querySelector(`.comment-more-${id}`).classList.remove('d-none');
  document.querySelector(`.comment-dots-${id}`).classList.add('d-none');
  document.querySelector(`.comment-show-more-${id}`).classList.add('d-none');
};
