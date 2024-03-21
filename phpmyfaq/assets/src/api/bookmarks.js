/**
 * Bookmark API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-09-19
 */

export const handleBookmarks = () => {
  const bookmarkTrashIcons = document.querySelectorAll('.pmf-delete-bookmark');

  if (bookmarkTrashIcons) {
    bookmarkTrashIcons.forEach((element) => {
      element.addEventListener('click', async (event) => {
        event.preventDefault();
        const bookmarkId = event.target.getAttribute('data-pmf-bookmark-id');

        try {
          const response = await fetch(`api/bookmark/${bookmarkId}`, {
            method: 'DELETE',
            cache: 'no-cache',
            headers: {
              'Content-Type': 'application/json',
            },
            redirect: 'follow',
            referrerPolicy: 'no-referrer',
          });

          if (!response.ok) {
            throw new Error('Network response was not ok');
          }

          const responseData = await response.json();
          const bookmarkToDelete = document.getElementById(`delete-bookmark-${bookmarkId}`);
          bookmarkToDelete.remove();
        } catch (error) {
          // Handle error here
          console.error('Error deleting bookmark:', error);
        }
      });
    });
  }
};
