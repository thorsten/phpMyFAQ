/**
 * Statistics Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-21
 */

export const handleStatistics = () => {
  const buttonsDeleteSearchTerm = document.querySelectorAll('.pmf-delete-search-term');

  if (buttonsDeleteSearchTerm) {
    buttonsDeleteSearchTerm.forEach((element) => {
      element.addEventListener('click', async (event) => {
        event.preventDefault();

        const searchTermId = event.target.getAttribute('data-delete-search-term-id');
        const csrf = event.target.getAttribute('data-csrf-token');

        if (confirm('Are you sure?')) {
          try {
            const response = await fetch('./api/search/term', {
              method: 'DELETE',
              headers: {
                Accept: 'application/json, text/plain, */*',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                csrf: csrf,
                searchTermId: searchTermId,
              }),
            });

            if (response.ok) {
              const jsonResponse = await response.json();
              const row = document.getElementById(`row-search-id-${jsonResponse.deleted}`);
              row.addEventListener('click', () => (row.style.opacity = '0'));
              row.addEventListener('transitionend', () => row.remove());
            } else {
              const errorMessage = await response.json();
              throw new Error(`Network response was not ok: ${errorMessage.error}`);
            }
          } catch (error) {
            console.error(error.message);
          }
        }
      });
    });
  }
};
