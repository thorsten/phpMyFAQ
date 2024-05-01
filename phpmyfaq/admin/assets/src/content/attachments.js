/**
 * Attachment administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */
import { deleteAttachments } from '../api/attachment';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleDeleteAttachments = () => {
  const attachmentTable = document.getElementById('attachment-table');

  if (attachmentTable) {
    attachmentTable.addEventListener('click', async (event) => {
      event.preventDefault();

      const isButton = event.target.className.includes('btn-delete-attachment');
      if (isButton) {
        const attachmentId = event.target.getAttribute('data-attachment-id');
        const csrf = event.target.getAttribute('data-csrf');

        const response = await deleteAttachments(attachmentId, csrf);

        if (response.success) {
          pushNotification(response.success);
          const row = document.getElementById(`attachment_${attachmentId}`);
          row.addEventListener('click', () => (row.style.opacity = '0'));
          row.addEventListener('transitionend', () => row.remove());
        }
        if (response.error) {
          pushErrorNotification(response.error);
        }
      }
    });
  }
};
