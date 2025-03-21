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

export const handleFileFilter = (): void => {
  const filterInput = document.getElementById('filter') as HTMLInputElement | null;
  const fileDivs = document.querySelectorAll('div.mce-file');

  if (filterInput) {
    filterInput.addEventListener('keyup', (event) => {
      const filter = (event.target as HTMLInputElement).value;
      fileDivs.forEach((fileDiv) => {
        if (fileDiv.textContent && fileDiv.textContent.search(new RegExp(filter, 'i')) < 0) {
          (fileDiv as HTMLElement).style.display = 'none';
        } else {
          (fileDiv as HTMLElement).style.display = 'block';
        }
      });
    });
  }

  document.addEventListener('click', (event: Event) => {
    const target = event.target as HTMLElement;
    if (target.matches('div.mce-file')) {
      const src = target.getAttribute('data-src');
      if (src) {
        window.parent.postMessage(
          {
            mceAction: 'phpMyFAQMediaBrowserAction',
            url: src,
          },
          '*'
        );
      }
    }
  });
};
