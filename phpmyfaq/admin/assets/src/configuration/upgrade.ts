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

import { addElement, versionCompare } from '../../../../assets/src/utils';
import {
  activateMaintenanceMode,
  checkForUpdates,
  downloadPackage,
  extractPackage,
  fetchHealthCheck,
  startDatabaseUpdate,
  startInstallation,
  startTemporaryBackup,
} from '../api';

interface ResponseData {
  success?: string;
  warning?: string;
  error?: string;
  dateLastChecked?: string;
  version?: string;
  message?: string;
}

interface ProgressData {
  progress?: string;
}

/**
 * Handles streaming progress updates for a progress bar
 * @param response - The fetch Response object with a readable stream
 * @param progressBarId - The ID of the progress bar element
 * @returns Promise that resolves when the stream is complete
 */
export const handleStreamingProgress = async (response: Response, progressBarId: string): Promise<void> => {
  const progressBar = document.getElementById(progressBarId);

  if (!progressBar) {
    console.error(`Progress bar element with id "${progressBarId}" not found`);
    return;
  }

  if (!response.body) {
    console.error('Response body is null, cannot stream progress');
    return;
  }

  const reader: ReadableStreamDefaultReader<Uint8Array> = response.body.getReader();
  const bar = progressBar; // Create a non-null reference for closure

  async function pump(): Promise<void> {
    const { done, value } = await reader.read();

    if (done) {
      bar.style.width = '100%';
      bar.innerText = '100%';
      bar.classList.remove('progress-bar-animated', 'bg-primary');
      bar.classList.add('bg-success');
      return;
    }

    const decodedValue: string = new TextDecoder().decode(value);
    try {
      const data: ProgressData = JSON.parse(decodedValue);
      if (data.progress) {
        bar.style.width = data.progress;
        bar.innerText = data.progress;
      }
    } catch (e) {
      // Ignore JSON parse errors for incomplete chunks
      console.debug('JSON parse error (likely incomplete chunk):', e);
    }

    return pump();
  }

  await pump();
};

export const handleCheckForUpdates = (): void => {
  const checkHealthButton = document.getElementById('pmf-button-check-health') as HTMLButtonElement;
  const checkUpdateButton = document.getElementById('pmf-button-check-updates') as HTMLButtonElement;
  const downloadButton = document.getElementById('pmf-button-download-now') as HTMLButtonElement;
  const extractButton = document.getElementById('pmf-button-extract-package') as HTMLButtonElement;
  const installButton = document.getElementById('pmf-button-install-package') as HTMLButtonElement;
  const buttonActivate = document.getElementById('pmf-button-activate-maintenance-mode') as HTMLButtonElement;

  // Health Check
  if (checkHealthButton) {
    checkHealthButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      try {
        const response = (await fetchHealthCheck()) as ResponseData;
        const result = document.getElementById('result-check-health') as HTMLElement;
        const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;

        if (response.success) {
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: response.success }));
        }
        if (response.warning) {
          card.classList.add('text-bg-warning');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: response.warning }));
          buttonActivate.classList.remove('d-none');
        }
        if (response.error) {
          card.classList.add('text-bg-danger');
          result.replaceWith(addElement('p', { id: 'result-check-health', innerText: response.error }));
        }
      } catch (error) {
        console.error(error);
      }
    });
  }

  // Activate Maintenance Mode
  if (buttonActivate) {
    buttonActivate.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      try {
        const csrfToken = buttonActivate.getAttribute('data-pmf-csrf') as string;
        const response = (await activateMaintenanceMode(csrfToken)) as ResponseData;
        const result = document.getElementById('result-check-health') as HTMLElement;
        const card = document.getElementById('pmf-update-step-health-check') as HTMLElement;

        if (response.success) {
          card.classList.remove('text-bg-warning');
          card.classList.add('text-bg-success');
          result.replaceWith(addElement('p', { innerText: response.success }));
          buttonActivate.classList.add('d-none');
        }
      } catch (error) {
        console.error(error);
      }
    });
  }

  // Check Update
  if (checkUpdateButton) {
    checkUpdateButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-check-versions') as HTMLElement;
      spinner.classList.remove('d-none');
      try {
        const response = (await checkForUpdates()) as ResponseData;
        const dateLastChecked = document.getElementById('dateLastChecked') as HTMLElement;
        const versionCurrent = document.getElementById('versionCurrent') as HTMLElement;
        const versionLastChecked = document.getElementById('versionLastChecked') as HTMLElement;
        const card = document.getElementById('pmf-update-step-check-versions') as HTMLElement;

        if (dateLastChecked && response.dateLastChecked) {
          const date = new Date(response.dateLastChecked);
          dateLastChecked.innerText = `${date.toLocaleString()}`;
        }

        if (versionCurrent && versionLastChecked && response.version) {
          const installedVersion = versionCurrent.innerText.trim();
          const availableVersion = response.version.trim();

          versionLastChecked.innerText = availableVersion;

          const result = document.getElementById('result-check-versions') as HTMLElement | null;
          if (result) {
            card.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger');

            const isNewer = versionCompare(availableVersion, installedVersion, '>=');

            if (isNewer) {
              card.classList.add('text-bg-success');
            } else {
              card.classList.add('text-bg-danger');
              downloadButton.disabled = true;
            }

            if (response.message) {
              result.replaceWith(addElement('p', { innerText: response.message }));
            }
            spinner.classList.add('d-none');
            checkUpdateButton.disabled = true;
          }
        } else {
          const result = document.getElementById('result-check-versions') as HTMLElement | null;
          if (result && response.message) {
            card.classList.add('text-bg-danger');
            result.replaceWith(addElement('p', { innerText: response.message }));
          }
          spinner.classList.add('d-none');
        }
      } catch (error) {
        console.error(error);
        spinner.classList.add('d-none');
      }
    });
  }

  // Download package
  if (downloadButton) {
    downloadButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      let version: string;
      const versionLastChecked = document.getElementById('versionLastChecked') as HTMLElement;
      const releaseEnvironment = document.getElementById('releaseEnvironment') as HTMLElement;
      const spinner = document.getElementById('spinner-download-new-version') as HTMLElement;
      spinner.classList.remove('d-none');

      if (releaseEnvironment && releaseEnvironment.innerText.toLowerCase() === 'nightly') {
        version = 'nightly';
      } else if (versionLastChecked) {
        version = versionLastChecked.innerText;
      } else {
        console.error('Version information not found');
        spinner.classList.add('d-none');
        return;
      }

      try {
        const response = (await downloadPackage(version)) as ResponseData;
        const result = document.getElementById('result-download-new-version') as HTMLElement;
        const divExtractPackage = document.getElementById('pmf-update-step-extract-package') as HTMLElement;
        const card = document.getElementById('pmf-update-step-download') as HTMLElement;

        if (response.success) {
          card.classList.add('text-bg-success');
          if (divExtractPackage) {
            divExtractPackage.classList.remove('d-none');
          }
          result.replaceWith(addElement('p', { innerText: response.success }));
          spinner.classList.add('d-none');
          downloadButton.disabled = true;
        }

        if (response.error) {
          card.classList.add('text-bg-danger');
          result.replaceWith(addElement('p', { innerText: response.error }));
          spinner.classList.add('d-none');
        }
      } catch (error) {
        const errorMessage = error as ResponseData;
        const result = document.getElementById('result-download-new-version') as HTMLElement;
        result.replaceWith(addElement('p', { innerText: errorMessage.error }));
        spinner.classList.add('d-none');
      }
    });
  }

  // Extract package
  if (extractButton) {
    extractButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const spinner = document.getElementById('spinner-extract-package') as HTMLElement;
      spinner.classList.remove('d-none');

      try {
        const response = (await extractPackage()) as ResponseData;
        const result = document.getElementById('result-extract-package') as HTMLElement;
        const divInstallPackage = document.getElementById('pmf-update-step-install-package') as HTMLElement;
        const card = document.getElementById('pmf-update-step-extract-package') as HTMLElement;

        if (result) {
          card.classList.add('text-bg-success');
          if (divInstallPackage) {
            divInstallPackage.classList.remove('d-none');
          }
          if (response.message) {
            result.replaceWith(addElement('p', { innerText: response.message }));
          }
          spinner.classList.add('d-none');
          extractButton.disabled = true;
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
    const response = (await startTemporaryBackup()) as unknown as Response;
    await handleStreamingProgress(response, 'result-backup-package');
  } catch (error) {
    console.error('Error during temporary backup:', error);
    const progressBar = document.getElementById('result-backup-package') as HTMLElement | null;
    if (progressBar) {
      progressBar.classList.remove('progress-bar-animated', 'bg-primary');
      progressBar.classList.add('bg-danger');
    }
    throw error;
  }

  await installPackage();
};

const installPackage = async (): Promise<void> => {
  try {
    const response = (await startInstallation()) as unknown as Response;
    await handleStreamingProgress(response, 'result-install-package');
  } catch (error) {
    console.error('Error during package installation:', error);
    const progressBar = document.getElementById('result-install-package') as HTMLElement | null;
    if (progressBar) {
      progressBar.classList.remove('progress-bar-animated', 'bg-primary');
      progressBar.classList.add('bg-danger');
    }
    throw error;
  }

  await updateDatabase();
};

const updateDatabase = async (): Promise<void> => {
  const card = document.getElementById('pmf-update-step-install-package') as HTMLElement | null;

  try {
    const response = (await startDatabaseUpdate()) as unknown as Response;
    await handleStreamingProgress(response, 'result-update-database');

    if (card) {
      card.classList.add('text-bg-success');
    }
  } catch (error) {
    console.error('Error during database update:', error);
    const progressBar = document.getElementById('result-update-database') as HTMLElement | null;
    if (progressBar) {
      progressBar.classList.remove('progress-bar-animated', 'bg-primary');
      progressBar.classList.add('bg-danger');
    }
    if (card) {
      card.classList.add('text-bg-danger');
    }
    throw error;
  }
};
