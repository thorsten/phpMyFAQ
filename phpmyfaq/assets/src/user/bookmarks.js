/**
 * Handle bookmarks page
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-03
 */

import { deleteBookmark } from '../api';
import { pushErrorNotification, pushNotification } from '../../../admin/assets/src/utils';

export const handleDeleteBookmarks = () => {
  const bookmarkTrashIcons = document.querySelectorAll('.pmf-delete-bookmark');
  if (bookmarkTrashIcons) {
    bookmarkTrashIcons.forEach((element) => {
      element.addEventListener('click', async (event) => {
        event.preventDefault();
        const bookmarkId = event.target.getAttribute('data-pmf-bookmark-id');
        const csrfToken = event.target.getAttribute('data-pmf-csrf');
        const bookmarkToDelete = document.getElementById(`delete-bookmark-${bookmarkId}`);
        bookmarkToDelete.remove();
        const response = await deleteBookmark(bookmarkId, csrfToken);
        if (response.success) {
          pushNotification(response.success);
        } else {
          pushErrorNotification(response.error);
        }
      });
    });
  }
};
