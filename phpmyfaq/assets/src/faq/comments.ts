/**
 * Comment functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-01-11
 */

import { Modal } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../utils';
import { createComment } from '../api';
import { ApiResponse, CommentData } from '../interfaces';
import { renderCommentEditor } from '../comment/editor';
import type { Jodit } from 'jodit';

// Store the Jodit editor instance
let joditInstance: Jodit | null = null;

export const handleSaveComment = (): void => {
  const saveButton = document.getElementById('pmf-button-save-comment') as HTMLButtonElement | null;
  const modalElement = document.getElementById('pmf-modal-add-comment') as HTMLElement | null;

  if (saveButton && modalElement) {
    saveButton.addEventListener('click', async (event: MouseEvent) => {
      event.preventDefault();
      event.stopPropagation();
      const form = document.querySelector('#pmf-add-comment-form') as HTMLFormElement;
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
      } else {
        try {
          // Sync Jodit content to textarea before form submission
          if (joditInstance) {
            const textarea = document.getElementById('comment_text') as HTMLTextAreaElement;
            if (textarea) {
              textarea.value = joditInstance.value;
            }
          }

          const comments = new FormData(form);
          const response = (await createComment(comments)) as ApiResponse;

          if (response.success) {
            pushNotification(response.success);

            // Add the new comment to the DOM if commentData is returned
            if (response.commentData) {
              addCommentToDOM(response.commentData);
            }
          }

          if (response.error) {
            pushErrorNotification(response.error);
          }

          // Close modal properly using Bootstrap's Modal API
          const bootstrapModal = Modal.getInstance(modalElement) || new Modal(modalElement);
          bootstrapModal.hide();

          // Destroy editor instance on successful submission
          destroyEditor();

          form.reset();
        } catch (error: unknown) {
          console.error('Error: ', error);
        }
      }
    });
  }

  // Initialize editor on modal show
  initializeCommentEditor();
};

/**
 * Initializes the comment editor when the modal is shown
 */
const initializeCommentEditor = (): void => {
  const modal = document.getElementById('pmf-modal-add-comment') as HTMLElement | null;

  if (!modal) {
    return;
  }

  // Check if editor should be enabled
  const enableEditor = modal.getAttribute('data-enable-editor') === 'true';
  const isLoggedIn = modal.getAttribute('data-is-logged-in') === 'true';

  if (!enableEditor || !isLoggedIn) {
    return;
  }

  // Listen for Bootstrap modal show event
  modal.addEventListener('show.bs.modal', () => {
    // Destroy existing instance if any
    destroyEditor();

    // Initialize new editor instance
    joditInstance = renderCommentEditor('#comment_text');
  });

  // Listen for Bootstrap modal hide event
  modal.addEventListener('hide.bs.modal', () => {
    destroyEditor();
  });
};

/**
 * Destroys the Jodit editor instance
 */
const destroyEditor = (): void => {
  if (joditInstance) {
    joditInstance.destruct();
    joditInstance = null;
  }
};

const addCommentToDOM = (commentData: CommentData): void => {
  const commentsContainer = document.getElementById('comments');
  if (!commentsContainer) {
    return;
  }

  // Format the date
  const date = new Date(parseInt(commentData.date) * 1000);
  const formattedDate = date.toLocaleString();

  // Escape HTML to prevent XSS
  const escapeHtml = (text: string): string => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  };

  const escapedUsername = escapeHtml(commentData.username);
  const escapedEmail = escapeHtml(commentData.email);

  const commentHtml = `
    <div class="row mt-2 mb-2">
      <div class="col-sm-1">
        <div class="thumbnail">
          <img src="${commentData.gravatarUrl}" alt="${escapedUsername}" class="img-thumbnail">
        </div>
      </div>
      <div class="col-sm-11">
        <div class="card">
          <div class="card-header card-header-comments">
            <strong><a href="mailto:${escapedEmail}">${escapedUsername}</a></strong>
            <span class="text-muted">(${formattedDate})</span>
          </div>
          <div class="card-body">${commentData.comment}</div>
        </div>
      </div>
    </div>
  `;

  // Insert the new comment at the end of the comments container
  commentsContainer.insertAdjacentHTML('beforeend', commentHtml);
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
