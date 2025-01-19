/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-01-11
 */

import { addElement } from '../utils';
import { createComment } from '../api';
import { ApiResponse } from '../interfaces';

export const handleSaveComment = (): void => {
  const saveButton = document.getElementById('pmf-button-save-comment') as HTMLButtonElement | null;
  const modal = document.getElementById('pmf-modal-add-comment') as HTMLElement | null;
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show') as HTMLCollectionOf<HTMLElement>;

  if (saveButton) {
    saveButton.addEventListener('click', async (event: MouseEvent) => {
      event.preventDefault();
      event.stopPropagation();
      const form = document.querySelector('#pmf-add-comment-form') as HTMLFormElement;
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
      } else {
        try {
          const comments = new FormData(form);
          const response = (await createComment(comments)) as ApiResponse;

          if (response.success) {
            const message = document.getElementById('pmf-comment-add-success') as HTMLElement;
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-success', innerText: response.success })
            );
          }

          if (response.error) {
            console.log('Error: ', response.error);
            const message = document.getElementById('pmf-comment-add-error') as HTMLElement;
            message.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: response.error })
            );
          }

          if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
          }
          if (modalBackdrop.length > 0) {
            modalBackdrop[0].parentNode?.removeChild(modalBackdrop[0]);
          }
          form.reset();
        } catch (error: any) {
          console.error('Error: ', error);
        }
      }
    });
  }
};

export const handleComments = (): void => {
  const showMoreComments = document.querySelectorAll('.pmf-comments-show-more') as NodeListOf<HTMLElement>;

  if (showMoreComments) {
    showMoreComments.forEach((element) => {
      element.addEventListener('click', (event: MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();

        const target = event.target as HTMLElement;
        const commentId = target.getAttribute('data-comment-id');
        if (commentId) {
          showLongComment(commentId);
        }
      });
    });
  }
};

const showLongComment = (id: string): void => {
  document.querySelector(`.comment-more-${id}`)?.classList.remove('d-none');
  document.querySelector(`.comment-dots-${id}`)?.classList.add('d-none');
  document.querySelector(`.comment-show-more-${id}`)?.classList.add('d-none');
};
