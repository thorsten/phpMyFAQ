/**
 * Upgrade related code.
 *
 * - Code for checking for updates.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-11
 */
import { addElement } from '../../../../assets/src/utils';

export const handleCheckForUpdates = () => {
  const checkUpdateButton = document.getElementById('pmf-button-check-updates');
  const downloadButton = document.getElementById('pmf-button-download-now');

  // Check Update
  if (checkUpdateButton) {
    checkUpdateButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(window.location.pathname + 'api/update-check', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
      })
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          const dateLastChecked = document.getElementById('dateLastChecked');
          if (dateLastChecked) {
            const date = new Date(response.dateLastChecked);
            dateLastChecked.innerText = `${date.toISOString()}`;
          }
          const result = document.getElementById('result-check-versions');
          if (result) {
            if (response.version === 'current') {
              result.replaceWith(addElement('p', { innerText: response.message }));
            } else {
              result.replaceWith(addElement('p', { innerText: response.message }));
            }
          }
        })
        .catch((error) => {
          console.error(error);
        });
    });
  }

  // Download package
  if (downloadButton) {
    downloadButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(window.location.pathname + 'api/download-package/nightly', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
      })
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          const result = document.getElementById('result-download-nightly');
          if (result) {
            if (response.version === 'current') {
              result.replaceWith(addElement('p', { innerText: response.message }));
            } else {
              result.replaceWith(addElement('p', { innerText: response.message }));
            }
          }
        })
        .catch((error) => {
          console.error(error);
        });
    });
  }
};
