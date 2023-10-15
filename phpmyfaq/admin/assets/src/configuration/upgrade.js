/**
 * Upgrade related code.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023 phpMyFAQ Team
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
  const installButton = document.getElementById('pmf-button-install-package');

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
          const card = document.getElementById('pmf-update-step-health-check');
          if (result) {
            card.classList.add('text-bg-success');
            if (response.success === 'ok') {
              result.replaceWith(addElement('p', { innerText: response.message }));
            } else {
              result.replaceWith(addElement('p', { innerText: response.message }));
            }
          }
        })
        .catch(async (error) => {
          const errorMessage = await error.cause.response.json();
          console.error(errorMessage);
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
          const card = document.getElementById('pmf-update-step-check-versions');

          if (dateLastChecked) {
            const date = new Date(response.dateLastChecked);
            dateLastChecked.innerText = `${date.toISOString()}`;
          }

          if (versionLastChecked) {
            versionLastChecked.innerText = response.version;
          }

          const result = document.getElementById('result-check-versions');
          if (result) {
            card.classList.add('text-bg-success');
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
          const card = document.getElementById('pmf-update-step-download');

          if (result) {
            card.classList.add('text-bg-success');
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
          const divInstallPackage = document.getElementById('pmf-update-step-install-package');
          const card = document.getElementById('pmf-update-step-extract-package');

          if (result) {
            card.classList.add('text-bg-success');
            divInstallPackage.classList.remove('d-none');
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

  // Install package
  if (installButton) {
    installButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(window.location.pathname + 'api/install-package', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
      })
        .then(async (response) => {
          const progressBar = document.getElementById('result-install-package');
          const reader = response.body.getReader();
          const card = document.getElementById('pmf-update-step-install-package');

          function pump() {
            return reader.read().then(({ done, value }) => {
              const decodedValue = new TextDecoder().decode(value);

              if (done) {
                progressBar.style.width = '100%';
                progressBar.innerText = '100%';
                progressBar.classList.remove('progress-bar-animated');
                card.classList.add('text-bg-success');
                return;
              } else {
                progressBar.style.width = JSON.parse(decodedValue).progress;
                progressBar.innerText = JSON.parse(decodedValue).progress;
              }

              return pump();
            });
          }

          return pump();
        })
        .catch((error) => {
          console.error(error);
        });
    });
  }
};
