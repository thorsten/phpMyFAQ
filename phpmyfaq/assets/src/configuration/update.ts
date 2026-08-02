/**
 * Update functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-22
 */

const getBasePath = (): string => {
  const path = window.location.pathname;
  let basePath: string;
  if (path.endsWith('/update/index.php')) {
    basePath = path.slice(0, -'/update/index.php'.length);
  } else if (path.endsWith('/update/')) {
    basePath = path.slice(0, -'/update/'.length);
  } else if (path.endsWith('/update')) {
    basePath = path.slice(0, -'/update'.length);
  } else {
    basePath = path;
  }

  if (!basePath.endsWith('/')) {
    basePath += '/';
  }

  return basePath;
};

const UPDATE_TOKEN_STORAGE_KEY = 'phpmyfaq-update-token';
const UPDATE_TOKEN_HEADER = 'x-pmf-update-token';

const getUpdateTokenField = (): HTMLInputElement | null =>
  document.getElementById('phpmyfaq-update-token') as HTMLInputElement | null;

/**
 * The update token is only entered on the first step, the following steps read it
 * back from the session storage.
 */
const getUpdateToken = (): string => {
  const tokenField = getUpdateTokenField();
  if (tokenField && tokenField.value.trim() !== '') {
    return tokenField.value.trim();
  }

  return window.sessionStorage.getItem(UPDATE_TOKEN_STORAGE_KEY) ?? '';
};

const getRequestHeaders = (): Record<string, string> => {
  const headers: Record<string, string> = {
    Accept: 'application/json, text/plain, */*',
    'Content-Type': 'application/json',
  };

  const token = getUpdateToken();
  if (token !== '') {
    headers[UPDATE_TOKEN_HEADER] = token;
  }

  return headers;
};

export const handleUpdateNextStepButton = (): void => {
  const nextStepButton = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement | null;
  const nextStep = document.getElementById('phpmyfaq-update-next-step') as HTMLInputElement | null;

  if (nextStepButton && nextStep) {
    nextStepButton.addEventListener('click', (event: MouseEvent): void => {
      event.preventDefault();
      const stepValue = parseInt(nextStep.value, 10);
      if (Number.isNaN(stepValue) || stepValue < 1) {
        return;
      }
      window.location.replace(`?step=${stepValue}`);
    });
  }
};

const runUpdateCheck = async (): Promise<void> => {
  const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

  if (!installedVersion) return;

  const basePath = getBasePath();
  const alertSuccess = document.getElementById('phpmyfaq-update-check-success') as HTMLElement | null;
  const alertError = document.getElementById('phpmyfaq-update-check-alert') as HTMLElement | null;
  const alertResult = document.getElementById('phpmyfaq-update-check-result') as HTMLElement | null;

  const showError = (message: string): void => {
    if (alertError && alertResult) {
      alertError.classList.remove('d-none');
      alertResult.innerText = message;
    }
  };

  try {
    const response = await fetch(`${basePath}api/setup/check`, {
      method: 'POST',
      headers: getRequestHeaders(),
      body: installedVersion.value,
    });

    if (!response.ok) {
      let errorText: string;
      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const errorMessage = await response.json();
        errorText = errorMessage.message || errorMessage.error || 'Update check failed';
      } else {
        const rawText = await response.text();
        if (!rawText || rawText === 'Not Found' || rawText.trimStart().startsWith('<')) {
          errorText =
            'The server returned an error (HTTP ' +
            response.status +
            '). Please check your server configuration: if you use Apache, verify the RewriteBase in your ' +
            '.htaccess matches your installation path. If you use nginx, check your rewrite configuration.';
        } else {
          errorText = rawText;
        }
      }
      showError(errorText);
      return;
    }

    // The token is accepted, so the following steps can reuse it
    const token = getUpdateToken();
    if (token !== '') {
      window.sessionStorage.setItem(UPDATE_TOKEN_STORAGE_KEY, token);
    }

    const button = document.getElementById('phpmyfaq-update-next-step-button') as HTMLButtonElement | null;

    if (alertError) {
      alertError.classList.add('d-none');
    }

    if (alertSuccess && button) {
      alertSuccess.classList.remove('d-none');
      button.classList.remove('disabled');
      button.disabled = false;
    }
  } catch {
    showError(
      'Could not connect to the update API. Please check your server configuration: ' +
        'if you use Apache, verify the RewriteBase in your .htaccess matches your installation path. ' +
        'If you use nginx, check your rewrite configuration.'
    );
  }
};

export const handleUpdateInformation = async (): Promise<void> => {
  const path = window.location.pathname;
  if (path.endsWith('/update') || path.endsWith('/update/') || path.endsWith('/update/index.php')) {
    const checkButton = document.getElementById('phpmyfaq-update-check-button') as HTMLButtonElement | null;

    // Without an administrator session, the update token has to be entered first,
    // so the check runs on demand instead of on page load.
    if (checkButton) {
      checkButton.addEventListener('click', (event: MouseEvent): void => {
        event.preventDefault();
        void runUpdateCheck();
      });
      return;
    }

    await runUpdateCheck();
  }
};

export const handleConfigBackup = async (): Promise<void> => {
  const href = window.location.href;
  if (href.includes('/update') && href.includes('step=2')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

    if (!installedVersion) return;

    const basePath = getBasePath();

    try {
      const response = await fetch(`${basePath}api/setup/backup`, {
        method: 'POST',
        headers: getRequestHeaders(),
        body: installedVersion.value,
      });

      if (!response.ok) {
        console.error('Network response was not ok');
        return;
      }

      await response.json();
    } catch (error: unknown) {
      console.error('Backup creation failed:', error);
    }
  }
};

export const handleDatabaseUpdate = async (): Promise<void> => {
  const href = window.location.href;
  if (href.includes('/update') && href.includes('step=3')) {
    const installedVersion = document.getElementById('phpmyfaq-update-installed-version') as HTMLInputElement | null;

    if (!installedVersion) return;

    const basePath = getBasePath();

    try {
      const response = await fetch(`${basePath}api/setup/update-database`, {
        method: 'POST',
        headers: getRequestHeaders(),
        body: installedVersion.value,
      });

      const result = await response.json();
      const progressBarInstallation = document.getElementById('result-update') as HTMLElement | null;

      if (progressBarInstallation) {
        progressBarInstallation.style.width = '100%';
        progressBarInstallation.innerText = '100%';
        progressBarInstallation.classList.remove('progress-bar-animated');
      }

      if (response.ok) {
        // The token is consumed by the update, keeping it around would only invite reuse
        window.sessionStorage.removeItem(UPDATE_TOKEN_STORAGE_KEY);

        const alert = document.getElementById('phpmyfaq-update-database-success') as HTMLElement | null;
        if (alert) {
          alert.classList.remove('d-none');
          alert.innerText = result.success;
        }
      } else {
        const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement | null;
        const errorMessage = document.getElementById('error-messages') as HTMLElement | null;
        if (alert && errorMessage) {
          alert.classList.remove('d-none');
          errorMessage.innerText = result.error || result.message || 'Update database failed.';
        }
      }
    } catch (error: unknown) {
      console.error('Error details:', error);
      const alert = document.getElementById('phpmyfaq-update-database-error') as HTMLElement | null;
      if (alert) {
        alert.classList.remove('d-none');
        alert.innerText = `Error: ${error instanceof Error ? error.message : String(error)}`;
      }
    }
  }
};
