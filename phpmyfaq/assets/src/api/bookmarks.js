/**
 * Bookmark API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-09-19
 */

export const handleBookmarks = () => {
  const bookmarkTrashIcons = document.querySelectorAll('.pmf-delete-bookmark');

  if (bookmarkTrashIcons) {
    bookmarkTrashIcons.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();
        const bookmarkId = event.target.getAttribute('data-pmf-bookmark-id');

        fetch(`api/bookmark/${bookmarkId}`, {
          method: 'DELETE',
          cache: 'no-cache',
          headers: {
            'Content-Type': 'application/json',
          },
          redirect: 'follow',
          referrerPolicy: 'no-referrer',
        })
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            const bookmarkToDelete = document.getElementById(`delete-bookmark-${bookmarkId}`);
            bookmarkToDelete.remove();
          })
          .catch(async (error) => {
            const errorMessage = await error.cause.response.json();
            return errorMessage.error;
          });
      });
    });
  }
};
