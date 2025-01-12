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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-11
 */

import { addElement } from '../../../../assets/src/utils';
import { fetchHealthCheck } from '../api';

export const handleCheckForUpdates = () => {
  const checkHealthButton = document.getElementById('pmf-button-check-health');
  const checkUpdateButton = document.getElementById('pmf-button-check-updates');
  const downloadButton = document.getElementById('pmf-button-download-now');
  const extractButton = document.getElementById('pmf-button-extract-package');
  const installButton = document.getElementById('pmf-button-install-package');

  // Health Check
  if (checkHealthButton) {
    checkHealthButton.addEventListener('click', async (event) => {
      event.preventDefault();
      try {
        const responseData = await fetchHealthCheck();
        const result = document.getElementById('result-check-health');
        const card = document.getElementById('pmf-update-step-health-check');

        if (responseData.success) {
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { innerText: responseData.success }));
        }
        if (responseData.warning) {
          card.classList.add('text-bg-warning');
          result.replaceWith(addElement('p', { innerText: responseData.warning }));
        }
        if (responseData.error) {
          card.classList.add('text-bg-danger');
          result.replaceWith(addElement('p', { innerText: responseData.error }));
        }
      } catch (error) {
        if (error.cause && error.cause.response) {
          const errorMessage = await error.cause.response.json();
          console.error(errorMessage);
        } else {
          console.error(error.message);
        }
      }
    });
  }

  // Check Update
  if (checkUpdateButton) {
    checkUpdateButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-check-versions');
      spinner.classList.remove('d-none');
      try {
        const response = await fetch('./api/update-check', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseData = await response.json();
        const dateLastChecked = document.getElementById('dateLastChecked');
        const versionLastChecked = document.getElementById('versionLastChecked');
        const card = document.getElementById('pmf-update-step-check-versions');

        if (dateLastChecked) {
          const date = new Date(responseData.dateLastChecked);
          dateLastChecked.innerText = `${date.toLocaleString()}`;
        }

        if (versionLastChecked) {
          versionLastChecked.innerText = responseData.version;
        }

        const result = document.getElementById('result-check-versions');
        if (result) {
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { innerText: responseData.message }));
          spinner.classList.add('d-none');
        }
      } catch (error) {
        console.error(error);
      }
    });
  }

  // Download package
  if (downloadButton) {
    downloadButton.addEventListener('click', async (event) => {
      event.preventDefault();

      let version;
      const versionLastChecked = document.getElementById('versionLastChecked');
      const releaseEnvironment = document.getElementById('releaseEnvironment');
      const spinner = document.getElementById('spinner-download-new-version');
      spinner.classList.remove('d-none');

      if (releaseEnvironment.innerText.toLowerCase() === 'nightly') {
        version = 'nightly';
      } else {
        version = versionLastChecked.innerText;
      }

      try {
        const response = await fetch(`./api/download-package/${version}`, {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseData = await response.json();
        const result = document.getElementById('result-download-new-version');
        const divExtractPackage = document.getElementById('pmf-update-step-extract-package');
        const card = document.getElementById('pmf-update-step-download');

        if (result) {
          card.classList.add('text-bg-success');
          divExtractPackage.classList.remove('d-none');
          result.replaceWith(addElement('p', { innerText: responseData.success }));
          spinner.classList.add('d-none');
        }
      } catch (error) {
        const errorMessage = await error.cause.response.json();
        const result = document.getElementById('result-download-new-version');
        result.replaceWith(addElement('p', { innerText: errorMessage.error }));
        spinner.classList.add('d-none');
      }
    });
  }

  // Extract package
  if (extractButton) {
    extractButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-extract-package');
      spinner.classList.remove('d-none');

      try {
        const response = await fetch('./api/extract-package', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseData = await response.json();
        const result = document.getElementById('result-extract-package');
        const divInstallPackage = document.getElementById('pmf-update-step-install-package');
        const card = document.getElementById('pmf-update-step-extract-package');

        if (result) {
          card.classList.add('text-bg-success');
          divInstallPackage.classList.remove('d-none');
          result.replaceWith(addElement('p', { innerText: responseData.message }));
          spinner.classList.add('d-none');
        }
      } catch (error) {
        console.error(error);
      }
    });
  }

  // Install package
  if (installButton) {
    installButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-install-package');
      spinner.classList.remove('d-none');
      await createTemporaryBackup();
      spinner.classList.add('d-none');
    });
  }
};

const createTemporaryBackup = async () => {
  try {
    const response = await fetch('./api/create-temporary-backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarBackup = document.getElementById('result-backup-package');
    const reader = response.body.getReader();

    async function pump() {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarBackup.style.width = '100%';
        progressBarBackup.innerText = '100%';
        progressBarBackup.classList.remove('progress-bar-animated');
        return;
      } else {
        progressBarBackup.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarBackup.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }

  await installPackage();
};

const installPackage = async () => {
  try {
    const response = await fetch('./api/install-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarInstallation = document.getElementById('result-install-package');
    const reader = response.body.getReader();
    const card = document.getElementById('pmf-update-step-install-package');

    async function pump() {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarInstallation.style.width = '100%';
        progressBarInstallation.innerText = '100%';
        progressBarInstallation.classList.remove('progress-bar-animated');
        return;
      } else {
        progressBarInstallation.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarInstallation.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }

  await updateDatabase();
};

const updateDatabase = async () => {
  try {
    const response = await fetch('./api/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarInstallation = document.getElementById('result-update-database');
    const reader = response.body.getReader();
    const card = document.getElementById('pmf-update-step-install-package');

    async function pump() {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarInstallation.style.width = '100%';
        progressBarInstallation.innerText = '100%';
        progressBarInstallation.classList.remove('progress-bar-animated');
        card.classList.add('text-bg-success');
        return;
      } else {
        progressBarInstallation.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarInstallation.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }
};
