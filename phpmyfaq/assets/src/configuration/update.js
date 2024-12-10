/**
 * Update functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-22
 */

export const handleUpdateNextStepButton = () => {
  const nextStepButton = document.getElementById('phpmyfaq-update-next-step-button');
  const nextStep = document.getElementById('phpmyfaq-update-next-step');

  if (nextStepButton && nextStep) {
    nextStepButton.addEventListener('click', (event) => {
      event.preventDefault();
      window.location.replace(`?step=${nextStep.value}`);
    });
  }
};

export const handleUpdateInformation = async () => {
  if (window.location.href.endsWith('/update/')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

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
        let errorMessage = await response.json();

        throw new Error(errorMessage.message);
      }

      const button = document.getElementById('phpmyfaq-update-next-step-button');
      const alert = document.getElementById('phpmyfaq-update-check-success');

      alert.classList.remove('d-none');
      button.classList.remove('disabled');
      button.disabled = false;
    } catch (errorMessage) {
      if (errorMessage instanceof SyntaxError) {
        errorMessage =
          'The requested resource was not found on the server. ' +
          'Please check your server configuration, especially the RewriteBase in your .htaccess configuration.';
      } else {
        errorMessage = errorMessage.message;
      }
      const alert = document.getElementById('phpmyfaq-update-check-alert');
      const alertResult = document.getElementById('phpmyfaq-update-check-result');

      alert.classList.remove('d-none');
      alertResult.innerText = errorMessage;
    }
  }
};

export const handleConfigBackup = async () => {
  if (window.location.href.endsWith('/update/?step=2')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

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
      const downloadLink = document.getElementById('phpmyfaq-update-backup-download-link');
      downloadLink.href = data.backupFile;
    } catch (error) {
      const errorMessage =
        error.cause && error.cause.response ? await error.cause.response.json() : { error: 'Unknown error' };
      return errorMessage.error;
    }
  }
};

export const handleDatabaseUpdate = async () => {
  if (window.location.href.endsWith('/update/?step=3')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

    try {
      const response = await fetch('../api/setup/update-database', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: installedVersion.value,
      });

      const progressBarInstallation = document.getElementById('result-update');
      const reader = response.body.getReader();

      async function pump() {
        const { done, value } = await reader.read();
        const decodedValue = new TextDecoder().decode(value);
        if (done) {
          progressBarInstallation.style.width = '100%';
          progressBarInstallation.innerText = '100%';
          progressBarInstallation.classList.remove('progress-bar-animated');
          const alert = document.getElementById('phpmyfaq-update-database-success');
          alert.classList.remove('d-none');
          return;
        } else {
          const value = JSON.parse(decodedValue);
          if (value.progress) {
            progressBarInstallation.style.width = value.progress;
            progressBarInstallation.innerText = value.progress;
          }
          if (value.error) {
            progressBarInstallation.style.width = '100%';
            progressBarInstallation.innerText = '100%';
            progressBarInstallation.classList.remove('progress-bar-animated');
            const alert = document.getElementById('phpmyfaq-update-database-error');
            const errorMessage = document.getElementById('error-messages');
            alert.classList.remove('d-none');
            errorMessage.innerHTML = value.error;
            return;
          }
        }

        await pump();
      }
      await pump();
    } catch (error) {
      const errorMessage =
        error.cause && error.cause.response ? await error.cause.response.json() : { error: 'Unknown error' };
      const alert = document.getElementById('phpmyfaq-update-database-error');

      alert.classList.remove('d-none');
      alert.innerHTML = errorMessage.error;
    }
  }
};
