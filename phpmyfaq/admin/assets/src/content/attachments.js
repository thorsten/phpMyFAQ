/**
 * Attachment administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */

export const handleDeleteAttachments = () => {
  const attachmentTable = document.getElementById('attachment-table');

  if (attachmentTable) {
    attachmentTable.addEventListener('click', (event) => {
      event.preventDefault();

      const isButton = event.target.className.includes('btn-delete-attachment');
      if (isButton) {
        const attachmentId = event.target.getAttribute('data-attachment-id');
        const csrf = event.target.getAttribute('data-csrf');

        fetch('index.php?action=ajax&ajax=att&ajaxaction=delete', {
          method: 'DELETE',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ attId: attachmentId, csrf: csrf }),
        })
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            const row = document.getElementById(`attachment_${attachmentId}`);
            row.addEventListener('click', () => (row.style.opacity = '0'));
            row.addEventListener('transitionend', () => row.remove());
          })
          .catch(async (error) => {
            console.error(await error.cause.response.json());
          });
      }
    });
  }
};
