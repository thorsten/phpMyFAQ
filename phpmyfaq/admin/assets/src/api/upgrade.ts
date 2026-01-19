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

import { fetchWrapper, fetchJson } from './fetch-wrapper';

interface ResponseData {
  success?: string;
  warning?: string;
  error?: string;
  dateLastChecked?: string;
  version?: string;
  message?: string;
}

export const fetchHealthCheck = async (): Promise<ResponseData> => {
  return (await fetchJson(`./api/health-check`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as ResponseData;
};

export const activateMaintenanceMode = async (csrfToken: string): Promise<ResponseData> => {
  return (await fetchJson('./api/configuration/activate-maintenance-mode', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ csrf: csrfToken }),
  })) as ResponseData;
};

export const checkForUpdates = async (): Promise<ResponseData> => {
  return (await fetchJson('./api/update-check', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  })) as ResponseData;
};

export const downloadPackage = async (version: string): Promise<ResponseData> => {
  return (await fetchJson(`./api/download-package/${version}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  })) as ResponseData;
};

export const extractPackage = async (): Promise<ResponseData> => {
  return (await fetchJson('./api/extract-package', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  })) as ResponseData;
};

export const startTemporaryBackup = async (): Promise<Response> => {
  return await fetchWrapper('./api/create-temporary-backup', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const startInstallation = async (): Promise<Response> => {
  return await fetchWrapper('./api/install-package', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const startDatabaseUpdate = async (): Promise<Response> => {
  return await fetchWrapper('./api/update-database', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
};
