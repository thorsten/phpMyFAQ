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
      window.location.replace(`./update.php?step=${nextStep.value}`);
    });
  }
};

export const handleUpdateInformation = () => {
  if (window.location.href.endsWith('update.php')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

    fetch('../../api/setup/check', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: installedVersion.value,
    })
      .then(async (response) => {
        if (response.ok) {
          return response.json();
        }
        throw new Error('Network response was not ok: ', { cause: { response } });
      })
      .then((data) => {
        const button = document.getElementById('phpmyfaq-update-next-step-button');
        const alert = document.getElementById('phpmyfaq-update-check-success');

        alert.classList.remove('d-none');

        button.classList.remove('disabled');
        button.disabled = false;
      })
      .catch(async (error) => {
        const errorMessage = await error.cause.response.json();
        const alert = document.getElementById('phpmyfaq-update-check-alert');
        const alertResult = document.getElementById('phpmyfaq-update-check-result');

        alert.classList.remove('d-none');
        alertResult.innerText = errorMessage.message;
      });
  }
};

export const handleConfigBackup = () => {
  if (window.location.href.endsWith('update.php?step=2')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

    fetch('../../api/setup/backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: installedVersion.value,
    })
      .then(async (response) => {
        if (response.ok) {
          return response.json();
        }
        throw new Error('Network response was not ok: ', { cause: { response } });
      })
      .then((data) => {
        const downloadLink = document.getElementById('phpmyfaq-update-backup-download-link');
        downloadLink.href = data.backupFile;
      })
      .catch(async (error) => {
        const errorMessage = await error.cause.response.json();
        return errorMessage.error;
      });
  }
};

export const handleDatabaseUpdate = async () => {
  if (window.location.href.endsWith('update.php?step=3')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version');

    await fetch('../../api/setup/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: installedVersion.value,
    })
      .then(async (response) => {
        const progressBarInstallation = document.getElementById('result-update');
        const reader = response.body.getReader();

        function pump() {
          return reader.read().then(({ done, value }) => {
            const decodedValue = new TextDecoder().decode(value);

            if (done) {
              progressBarInstallation.style.width = '100%';
              progressBarInstallation.innerText = '100%';
              progressBarInstallation.classList.remove('progress-bar-animated');
              const alert = document.getElementById('phpmyfaq-update-database-success');
              alert.classList.remove('d-none');
              return;
            } else {
              progressBarInstallation.style.width = JSON.parse(decodedValue).progress;
              progressBarInstallation.innerText = JSON.parse(decodedValue).progress;
            }

            return pump();
          });
        }
        return pump();
      })
      .catch(async (error) => {
        const errorMessage = await error.cause.response.json();
        const alert = document.getElementById('phpmyfaq-update-database-error');

        alert.classList.remove('d-none');
        alert.innerHTML = errorMessage.error;
      });
  }
};
