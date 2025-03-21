/**
 * FAQ functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-16
 */

import { addElement } from '../utils';
import { createBookmark, createFaq, deleteBookmark } from '../api';
import { ApiResponse } from '../interfaces';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleAddFaq = () => {
  const addFaqSubmit = document.getElementById('pmf-submit-faq');

  if (addFaqSubmit) {
    addFaqSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation') as HTMLFormElement;
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-add-faq-form') as HTMLFormElement;
        const loader = document.getElementById('loader') as HTMLElement;
        const formData = new FormData(form);
        const response = (await createFaq(formData)) as ApiResponse;

        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response') as HTMLElement;
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response') as HTMLElement;
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};

export const handleShowFaq = () => {
  const bookmarkToggle = document.getElementById('pmf-bookmark-toggle');
  if (bookmarkToggle) {
    bookmarkToggle.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      const csrfToken = bookmarkToggle.getAttribute('data-pmf-csrf') as string;
      const id = bookmarkToggle.getAttribute('data-pmf-id') as string;
      if (bookmarkToggle.getAttribute('data-pmf-action') === 'remove') {
        const response = await deleteBookmark(id as string, csrfToken);
        if (response.success) {
          pushNotification(response.success);
          const bookmarkIcon = document.getElementById('pmf-bookmark-icon');
          if (bookmarkIcon) {
            bookmarkIcon.classList.remove('bi-bookmark-fill');
            bookmarkIcon.classList.add('bi-bookmark');
          }
          bookmarkToggle.innerText = response.linkText;
          bookmarkToggle.setAttribute('data-pmf-action', 'add');
          bookmarkToggle.setAttribute('data-pmf-csrf', response.csrfToken);
        } else {
          pushErrorNotification(response.error);
        }
      } else {
        const response = await createBookmark(id, csrfToken);
        if (response.success) {
          pushNotification(response.success);
          const bookmarkIcon = document.getElementById('pmf-bookmark-icon');
          if (bookmarkIcon) {
            bookmarkIcon.classList.remove('bi-bookmark');
            bookmarkIcon.classList.add('bi-bookmark-fill');
          }
          bookmarkToggle.innerText = response.linkText;
          bookmarkToggle.setAttribute('data-pmf-action', 'remove');
          bookmarkToggle.setAttribute('data-pmf-csrf', response.csrfToken);
        } else {
          pushErrorNotification(response.error);
        }
      }
    });
  }
};

export const handleShareLinkButton = () => {
  const copyButton = document.getElementById('pmf-share-link-copy-button');

  if (copyButton) {
    copyButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      const shareLink = document.getElementById('pmf-share-link') as HTMLInputElement | null;
      const shareButton = document.getElementById('pmf-share-link-copy-button');
      const message = shareButton?.getAttribute('data-pmf-message');
      if (shareLink && message) {
        await navigator.clipboard.writeText(shareLink.value);
        pushNotification(message);
      }
    });
  }
};
