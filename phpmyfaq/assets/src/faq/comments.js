/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-01-11
 */

import { addElement, serialize } from '../utils';
import { createComment } from '../api';

export const handleSaveComment = () => {
  const saveButton = document.getElementById('pmf-button-save-comment');
  const modal = document.getElementById('pmf-modal-add-comment');
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');

  if (saveButton) {
    saveButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      const form = document.querySelector('#pmf-add-comment-form');
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
      } else {
        try {
          const comments = new FormData(form);
          const response = await createComment(comments);

          if (response.success) {
            const message = document.getElementById('pmf-comment-add-success');
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-success', innerText: response.success })
            );
          }

          if (response.error) {
            console.log('Error: ', response.error);
            const message = document.getElementById('pmf-comment-add-error');
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: response.error })
            );
          }

          modal.style.display = 'none';
          modal.classList.remove('show');
          modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
          form.reset();
        } catch (error) {
          console.error('Error: ', error);
        }
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
