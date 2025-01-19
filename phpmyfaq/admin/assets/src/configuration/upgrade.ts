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

interface ResponseData {
  success?: string;
  warning?: string;
  error?: string;
  dateLastChecked?: string;
  version?: string;
  message?: string;
}

export const handleCheckForUpdates = (): void => {
  const checkHealthButton = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
  const checkUpdateButton = document.getElementById('pmf-button-check-updates') as HTMLButtonElement;
  const downloadButton = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
  const extractButton = document.getElementById('pmf-button-extract-package') as HTMLButtonElement;
  const installButton = document.getElementById('pmf-button-install-package') as HTMLButtonElement;
  const buttonActivate = document.getElementById('pmf-button-activate-maintenance-mode') as HTMLButtonElement;

  // Health Check
  if (checkHealthButton) {
    checkHealthButton.addEventListener('click', async (event) => {
      event.preventDefault();
      try {
        const responseData = (await fetchHealthCheck()) as ResponseData;
        const result = document.getElementById('result-check-health') as HTMLElement;
        const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;

        if (responseData.success) {
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: responseData.success }));
        }
        if (responseData.warning) {
          card.classList.add('text-bg-warning');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: responseData.warning }));
          buttonActivate.classList.remove('d-none');
        }
        if (responseData.error) {
          card.classList.add('text-bg-danger');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: responseData.error }));
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

  // Activate Maintenance Mode
  if (buttonActivate) {
    buttonActivate.addEventListener('click', async (event) => {
      event.preventDefault();
      try {
        const response = await fetch('./api/configuration/activate-maintenance-mode', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ csrf: buttonActivate.getAttribute('data-pmf-csrf') }),
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseData: ResponseData = await response.json();
        const result = document.getElementById('result-check-health') as HTMLElement;
        const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;

        if (responseData.success) {
          card.classList.remove('text-bg-warning');
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { innerText: responseData.success }));
          buttonActivate.classList.add('d-none');
        }
      } catch (error) {
        console.error(error);
      }
    });
  }

  // Check Update
  if (checkUpdateButton) {
    checkUpdateButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-check-versions') as HTMLElement;
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

        const responseData: ResponseData = await response.json();
        const dateLastChecked = document.getElementById('dateLastChecked') as HTMLElement;
        const versionLastChecked = document.getElementById('versionLastChecked') as HTMLElement;
        const card = document.getElementById('pmf-update-step-check-versions') as HTMLElement;

        if (dateLastChecked) {
          const date = new Date(responseData.dateLastChecked!);
          dateLastChecked.innerText = `${date.toLocaleString()}`;
        }

        if (versionLastChecked) {
          versionLastChecked.innerText = responseData.version!;
        }

        const result = document.getElementById('result-check-versions');
        if (result) {
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { innerText: responseData.message! }));
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

      let version: string;
      const versionLastChecked = document.getElementById('versionLastChecked') as HTMLElement;
      const releaseEnvironment = document.getElementById('releaseEnvironment') as HTMLElement;
      const spinner = document.getElementById('spinner-download-new-version') as HTMLElement;
      spinner.classList.remove('d-none');

      if (releaseEnvironment!.innerText.toLowerCase() === 'nightly') {
        version = 'nightly';
      } else {
        version = versionLastChecked!.innerText;
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

        const responseData: ResponseData = await response.json();
        const result = document.getElementById('result-download-new-version') as HTMLElement;
        const divExtractPackage = document.getElementById('pmf-update-step-extract-package') as HTMLElement;
        const card = document.getElementById('pmf-update-step-download') as HTMLElement;

        if (result) {
          card.classList.add('text-bg-success');
          divExtractPackage!.classList.remove('d-none');
          result.replaceWith(addElement('p', { innerText: responseData.success! }));
          spinner.classList.add('d-none');
        }
      } catch (error) {
        const errorMessage = await error.cause.response.json();
        const result = document.getElementById('result-download-new-version') as HTMLElement;
        result.replaceWith(addElement('p', { innerText: errorMessage.error }));
        spinner.classList.add('d-none');
      }
    });
  }

  // Extract package
  if (extractButton) {
    extractButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-extract-package') as HTMLElement;
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

        const responseData: ResponseData = await response.json();
        const result = document.getElementById('result-extract-package') as HTMLElement;
        const divInstallPackage = document.getElementById('pmf-update-step-install-package') as HTMLElement;
        const card = document.getElementById('pmf-update-step-extract-package') as HTMLElement;

        if (result) {
          card.classList.add('text-bg-success');
          divInstallPackage!.classList.remove('d-none');
          result.replaceWith(addElement('p', { innerText: responseData.message! }));
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
      const spinner = document.getElementById('spinner-install-package') as HTMLElement;
      spinner.classList.remove('d-none');
      await createTemporaryBackup();
      spinner.classList.add('d-none');
    });
  }
};

const createTemporaryBackup = async (): Promise<void> => {
  try {
    const response = await fetch('./api/create-temporary-backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarBackup = document.getElementById('result-backup-package');
    const reader = response.body!.getReader();

    async function pump(): Promise<void> {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarBackup!.style.width = '100%';
        progressBarBackup!.innerText = '100%';
        progressBarBackup!.classList.remove('progress-bar-animated');
        return;
      } else {
        progressBarBackup!.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarBackup!.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }

  await installPackage();
};

const installPackage = async (): Promise<void> => {
  try {
    const response = await fetch('./api/install-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarInstallation = document.getElementById('result-install-package');
    const reader = response.body!.getReader();

    async function pump(): Promise<void> {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarInstallation!.style.width = '100%';
        progressBarInstallation!.innerText = '100%';
        progressBarInstallation!.classList.remove('progress-bar-animated');
        return;
      } else {
        progressBarInstallation!.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarInstallation!.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }

  await updateDatabase();
};

const updateDatabase = async (): Promise<void> => {
  try {
    const response = await fetch('./api/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    const progressBarInstallation = document.getElementById('result-update-database');
    const reader = response.body!.getReader();
    const card = document.getElementById('pmf-update-step-install-package') as HTMLElement;

    async function pump(): Promise<void> {
      const { done, value } = await reader.read();
      const decodedValue = new TextDecoder().decode(value);

      if (done) {
        progressBarInstallation!.style.width = '100%';
        progressBarInstallation!.innerText = '100%';
        progressBarInstallation!.classList.remove('progress-bar-animated');
        card.classList.add('text-bg-success');
        return;
      } else {
        progressBarInstallation!.style.width = JSON.parse(JSON.stringify(decodedValue)).progress;
        progressBarInstallation!.innerText = JSON.parse(JSON.stringify(decodedValue)).progress;
      }

      return pump();
    }

    await pump();
  } catch (error) {
    console.error(error);
  }
};
