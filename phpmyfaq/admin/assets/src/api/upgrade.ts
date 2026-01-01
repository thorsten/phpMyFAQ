/**
 * Upgrade API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
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
};

export const activateMaintenanceMode = async (csrfToken: string): Promise<ResponseData> => {
  const response: Response = await fetch('./api/configuration/activate-maintenance-mode', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ csrf: csrfToken }),
  });

  return await response.json();
};

export const checkForUpdates = async (): Promise<ResponseData> => {
  const response: Response = await fetch('./api/update-check', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });

  return await response.json();
};

export const downloadPackage = async (version: string): Promise<ResponseData> => {
  const response: Response = await fetch(`./api/download-package/${version}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });

  return await response.json();
};

export const extractPackage = async (): Promise<ResponseData> => {
  const response: Response = await fetch('./api/extract-package', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });

  return await response.json();
};

export const startTemporaryBackup = async (): Promise<Response> => {
  return await fetch('./api/create-temporary-backup', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });
};

export const startInstallation = async (): Promise<Response> => {
  return await fetch('./api/install-package', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });
};

export const startDatabaseUpdate = async (): Promise<Response> => {
  return await fetch('./api/update-database', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
  });
};
