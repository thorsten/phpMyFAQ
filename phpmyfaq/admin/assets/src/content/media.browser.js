/**
 * Media browser administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-07
 */

export const handleFileFilter = () => {
  const filterInput = document.getElementById('filter');
  const fileDivs = document.querySelectorAll('div.mce-file');

  if (filterInput) {
    filterInput.addEventListener('keyup', function () {
      const filter = this.value;
      fileDivs.forEach((fileDiv) => {
        if (fileDiv.textContent.search(new RegExp(filter, 'i')) < 0) {
          fileDiv.style.display = 'none';
        } else {
          fileDiv.style.display = 'block';
        }
      });
    });
  }

  document.addEventListener('click', (event) => {
    if (event.target.matches('div.mce-file')) {
      const src = event.target.getAttribute('data-src');
      window.parent.postMessage(
        {
          mceAction: 'phpMyFAQMediaBrowserAction',
          url: src,
        },
        '*'
      );
    }
  });
};
