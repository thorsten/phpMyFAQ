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
  const checkHealthButton = document.getElementById('pmf-button-check-health');
  const checkUpdateButton = document.getElementById('pmf-button-check-updates');
  const downloadButton = document.getElementById('pmf-button-download-now');
  const extractButton = document.getElementById('pmf-button-extract-package');

  // Health Check
  if (checkHealthButton) {
    checkHealthButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(window.location.pathname + 'api/health-check', {
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
          const result = document.getElementById('result-check-health');
          if (result) {
            if (response.success === 'ok') {
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
          const versionLastChecked = document.getElementById('versionLastChecked');

          if (dateLastChecked) {
            const date = new Date(response.dateLastChecked);
            dateLastChecked.innerText = `${date.toISOString()}`;
          }

          if (versionLastChecked) {
            versionLastChecked.innerText = response.version;
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

      let version;
      const versionLastChecked = document.getElementById('versionLastChecked');
      const releaseEnvironment = document.getElementById('releaseEnvironment');

      if (releaseEnvironment.innerText.toLowerCase() === 'nightly') {
        version = 'nightly';
      } else {
        version = versionLastChecked;
      }

      fetch(window.location.pathname + `api/download-package/${version}`, {
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
          const divExtractPackage = document.getElementById('pmf-update-step-extract-package');
          if (result) {
            divExtractPackage.classList.remove('d-none');
            if (response.version === 'current') {
              result.replaceWith(addElement('p', { innerText: response.success }));
            } else {
              result.replaceWith(addElement('p', { innerText: response.success }));
            }
          }
        })
        .catch(async (error) => {
          const errorMessage = await error.cause.response.json();
          const result = document.getElementById('result-download-nightly');
          result.replaceWith(addElement('p', { innerText: errorMessage.error }));
        });
    });
  }

  // Extract package
  if (extractButton) {
    extractButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(window.location.pathname + 'api/extract-package', {
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
          const result = document.getElementById('result-extract-package');
          if (result) {
            if (response.success === 'ok') {
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
