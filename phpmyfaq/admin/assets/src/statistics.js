/**
 * Statistics Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-21
 */

import { addElement } from '../../../assets/src/utils';

export const handleStatistics = () => {
  const buttonsDeleteSearchTerm = document.querySelectorAll('.pmf-delete-search-term');

  if (buttonsDeleteSearchTerm) {
    buttonsDeleteSearchTerm.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const searchTermId = event.target.getAttribute('data-delete-search-term-id');
        const csrf = event.target.getAttribute('data-csrf-token');

        if (confirm('Are you sure?')) {
          fetch('index.php?action=ajax&ajax=search&ajaxaction=delete_searchterm', {
            method: 'DELETE',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: csrf,
              searchTermId: searchTermId,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
                return response.json();
              }
              throw new Error('Network response was not ok.');
            })
            .then((response) => {
              const row = document.getElementById(`row-search-id-${response.deleted}`);
              row.addEventListener('click', () => (row.style.opacity = '0'));
              row.addEventListener('transitionend', () => row.remove());
            })
            .catch((error) => {
              const table = document.querySelector('.table');
              table.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-danger', innerText: error })
              );
            });
        }
      });
    });
  }
};
