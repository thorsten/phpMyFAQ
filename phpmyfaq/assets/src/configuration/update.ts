/**
 * Update functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-22
 */

export const handleUpdateNextStepButton = (): void => {
  const nextStepButton = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement | null;
  const nextStep = document.getElementById('phpmyfaq-update-next-step') as HTMLInputElement | null;

  if (nextStepButton && nextStep) {
    nextStepButton.addEventListener('click', (event: MouseEvent): void => {
      event.preventDefault();
      window.location.replace(`?step=${nextStep.value}`);
    });
  }
};

export const handleUpdateInformation = async (): Promise<void> => {
  if (window.location.href.endsWith('/update/') || window.location.href.endsWith('/update/index.php')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

    if (!installedVersion) return;

    try {
      const response = await fetch('../api/setup/check', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: installedVersion.value,
      });

      if (!response.ok) {
        const errorMessage = await response.json();
        throw new Error(errorMessage.message);
      }

      const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement | null;
      const alert = document.getElementById('phpmyfaq-update-check-success') as HTMLElement | null;

      if (alert && button) {
        alert.classList.remove('d-none');
        button.classList.remove('disabled');
        button.disabled = false;
      }
    } catch (error: any) {
      let errorMessage: string;
      if (error instanceof SyntaxError) {
        errorMessage =
          'The requested resource was not found on the server. ' +
          "Please check your server configuration, in case you're using Apache, " +
          'the RewriteBase in your .htaccess configuration.';
      } else {
        errorMessage = error.message;
      }
      const alert = document.getElementById('phpmyfaq-update-check-alert') as HTMLElement | null;
      const alertResult = document.getElementById('phpmyfaq-update-check-result') as HTMLElement | null;

      if (alert && alertResult) {
        alert.classList.remove('d-none');
        alertResult.innerText = errorMessage;
      }
    }
  }
};

export const handleConfigBackup = async (): Promise<void> => {
  if (window.location.href.endsWith('/update/?step=2') || window.location.href.endsWith('/update/index.php?step=2')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

    if (!installedVersion) return;

    try {
      const response = await fetch('../api/setup/backup', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: installedVersion.value,
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const data = await response.json();
      const downloadLink = document.getElementById('phpmyfaq-update-backup-download-link') as HTMLAnchorElement | null;
      if (downloadLink) {
        downloadLink.href = data.backupFile;
      }
    } catch (error: any) {
      const errorMessage =
        error.cause && error.cause.response ? await error.cause.response.json() : { error: 'Unknown error' };
      console.error(errorMessage.error);
    }
  }
};

export const handleDatabaseUpdate = async (): Promise<void> => {
  if (window.location.href.endsWith('/update/?step=3') || window.location.href.endsWith('/update/index.php?step=3')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

    if (!installedVersion) return;

    try {
      const response = await fetch('../api/setup/update-database', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: installedVersion.value,
      });

      const result = await response.json();
      const progressBarInstallation = document.getElementById('result-update') as HTMLElement | null;

      if (response.ok) {
        if (progressBarInstallation) {
          progressBarInstallation.style.width = '100%';
          progressBarInstallation.innerText = '100%';
          progressBarInstallation.classList.remove('progress-bar-animated');
        }
        const alert = document.getElementById('phpmyfaq-update-database-success') as HTMLElement | null;
        if (alert) {
          alert.classList.remove('d-none');
          alert.innerText = result.success;
        }
      } else {
        if (progressBarInstallation) {
          progressBarInstallation.style.width = '100%';
          progressBarInstallation.innerText = '100%';
          progressBarInstallation.classList.remove('progress-bar-animated');
        }
        const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement | null;
        const errorMessage = document.getElementById('error-messages') as HTMLElement | null;
        if (alert && errorMessage) {
          alert.classList.remove('d-none');
          errorMessage.innerHTML = result.error;
        }
      }
    } catch (error: any) {
      console.error('Error details:', error);
      const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement | null;
      if (alert) {
        alert.classList.remove('d-none');
        alert.innerText = `Error: ${error.message}`;
      }
    }
  }
};
