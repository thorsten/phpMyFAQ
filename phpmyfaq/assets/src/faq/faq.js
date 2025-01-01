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
import { pushErrorNotification, pushNotification } from '../../../admin/assets/src/utils';

export const handleAddFaq = () => {
  const addFaqSubmit = document.getElementById('pmf-submit-faq');

  if (addFaqSubmit) {
    addFaqSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-add-faq-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await createFaq(formData);

        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response');
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
      const csrfToken = bookmarkToggle.getAttribute('data-pmf-csrf');
      if (bookmarkToggle.getAttribute('data-pmf-action') === 'remove') {
        const response = await deleteBookmark(bookmarkToggle.getAttribute('data-pmf-id'), csrfToken);
        if (response.success) {
          pushNotification(response.success);
          document.getElementById('pmf-bookmark-icon').classList.remove('bi-bookmark-fill');
          document.getElementById('pmf-bookmark-icon').classList.add('bi-bookmark');
          bookmarkToggle.innerText = response.linkText;
          bookmarkToggle.setAttribute('data-pmf-action', 'add');
          bookmarkToggle.setAttribute('data-pmf-csrf', response.csrfToken);
        } else {
          pushErrorNotification(response.error);
        }
      } else {
        const response = await createBookmark(bookmarkToggle.getAttribute('data-pmf-id'), csrfToken);
        if (response.success) {
          pushNotification(response.success);
          document.getElementById('pmf-bookmark-icon').classList.remove('bi-bookmark');
          document.getElementById('pmf-bookmark-icon').classList.add('bi-bookmark-fill');
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
      const shareLink = document.getElementById('pmf-share-link');
      const shareButton = document.getElementById('pmf-share-link-copy-button');
      const message = shareButton.getAttribute('data-pmf-message');
      navigator.clipboard.writeText(shareLink.value);
      pushNotification(message);
    });
  }
};
