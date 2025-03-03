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

import { deleteAttachments, refreshAttachments } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const handleDeleteAttachments = (): void => {
  const deleteButtons = document.querySelectorAll<HTMLButtonElement>('.btn-delete-attachment');

  if (deleteButtons.length > 0) {
    deleteButtons.forEach((button) => {
      const newButton = button.cloneNode(true) as HTMLButtonElement;
      button.replaceWith(newButton);

      newButton.addEventListener('click', async (event: MouseEvent) => {
        event.preventDefault();

        const attachmentId = newButton.getAttribute('data-attachment-id');
        const csrf = newButton.getAttribute('data-csrf');

        if (attachmentId && csrf) {
          const response = await deleteAttachments(attachmentId, csrf);

          if (response.success) {
            pushNotification(response.success);
            const row = document.getElementById(`attachment_${attachmentId}`) as HTMLElement;
            row.style.opacity = '0';
            row.addEventListener('transitionend', () => row.remove());
          }
          if (response.error) {
            pushErrorNotification(response.error);
          }
        }
      });
    });
  }
};

export const handleRefreshAttachments = (): void => {
  const refreshButtons = document.querySelectorAll<HTMLButtonElement>(
    '.btn-refresh-attachment'
  ) as NodeListOf<HTMLButtonElement>;

  if (refreshButtons.length > 0) {
    refreshButtons.forEach((button: HTMLButtonElement): void => {
      const newButton = button.cloneNode(true) as HTMLButtonElement;
      button.replaceWith(newButton);

      newButton.addEventListener('click', async (event: MouseEvent): Promise<void> => {
        event.preventDefault();

        const attachmentId = newButton.getAttribute('data-attachment-id') as string;
        const csrf = newButton.getAttribute('data-csrf') as string;

        if (attachmentId && csrf) {
          const response = (await refreshAttachments(attachmentId, csrf)) as unknown as Response;

          if (response.success) {
            pushNotification(response.success);
            if (response.delete) {
              const row = document.getElementById(`attachment_${attachmentId}`) as HTMLElement;
              row.style.opacity = '0';
              row.addEventListener('transitionend', () => row.remove());
            }
          }
          if (response.error) {
            pushErrorNotification(response.error);
          }
        }
      });
    });
  }
};
