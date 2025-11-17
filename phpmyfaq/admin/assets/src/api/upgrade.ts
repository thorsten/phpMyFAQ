/**
 * Upgrade API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-05-30
 */

interface ResponseData {
  success?: string;
  warning?: string;
  error?: string;
  dateLastChecked?: string;
  version?: string;
  message?: string;
}

export const fetchHealthCheck = async (): Promise<ResponseData> => {
  try {
    const response: Response = await fetch(`./api/health-check`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const activateMaintenanceMode = async (csrfToken: string): Promise<ResponseData> => {
  try {
    const response: Response = await fetch('./api/configuration/activate-maintenance-mode', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ csrf: csrfToken }),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const checkForUpdates = async (): Promise<ResponseData> => {
  try {
    const response: Response = await fetch('./api/update-check', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const downloadPackage = async (version: string): Promise<ResponseData> => {
  try {
    const response: Response = await fetch(`./api/download-package/${version}`, {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const extractPackage = async (): Promise<ResponseData> => {
  try {
    const response: Response = await fetch('./api/extract-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const startTemporaryBackup = async (): Promise<Response> => {
  try {
    const response = await fetch('./api/create-temporary-backup', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return response;
  } catch (error) {
    throw error;
  }
};

export const startInstallation = async (): Promise<Response> => {
  try {
    const response: Response = await fetch('./api/install-package', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return response;
  } catch (error) {
    throw error;
  }
};

export const startDatabaseUpdate = async (): Promise<Response> => {
  try {
    const response: Response = await fetch('./api/update-database', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    return response;
  } catch (error) {
    throw error;
  }
};
