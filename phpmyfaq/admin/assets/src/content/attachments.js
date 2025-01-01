/**
 * Attachment administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */
import { deleteAttachments, refreshAttachments } from '../api/attachment';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleDeleteAttachments = () => {
  const deleteButtons = document.querySelectorAll('.btn-delete-attachment');

  if (deleteButtons.length > 0) {
    deleteButtons.forEach((button) => {
      const newButton = button.cloneNode(true);
      button.replaceWith(newButton);

      newButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const attachmentId = newButton.getAttribute('data-attachment-id');
        const csrf = newButton.getAttribute('data-csrf');

        const response = await deleteAttachments(attachmentId, csrf);

        if (response.success) {
          pushNotification(response.success);
          const row = document.getElementById(`attachment_${attachmentId}`);
          row.style.opacity = '0';
          row.addEventListener('transitionend', () => row.remove());
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      });
    });
  }
};

export const handleRefreshAttachments = () => {
  const refreshButton = document.querySelectorAll('.btn-refresh-attachment');

  if (refreshButton.length > 0) {
    refreshButton.forEach((button) => {
      const newButton = button.cloneNode(true);
      button.replaceWith(newButton);

      newButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const attachmentId = newButton.getAttribute('data-attachment-id');
        const csrf = newButton.getAttribute('data-csrf');

        const response = await refreshAttachments(attachmentId, csrf);

        if (response.success) {
          pushNotification(response.success);
          if (response.delete) {
            const row = document.getElementById(`attachment_${attachmentId}`);
            row.style.opacity = '0';
            row.addEventListener('transitionend', () => row.remove());
          }
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      });
    });
  }
};
