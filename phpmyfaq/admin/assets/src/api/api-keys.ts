/**
 * Fetch data for API key management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-22
 */

import { fetchJson } from './fetch-wrapper';
import { ApiKey, ApiKeyPayload, ApiKeyResponse } from '../interfaces';

export const fetchApiKeys = async (): Promise<ApiKey[]> => {
  return await fetchJson<ApiKey[]>('./api/user/api-keys', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const createApiKey = async (payload: ApiKeyPayload, csrf: string): Promise<ApiKeyResponse> => {
  return await fetchJson<ApiKeyResponse>('./api/user/api-keys', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ ...payload, csrf }),
  });
};

export const updateApiKey = async (
  id: number | string,
  payload: ApiKeyPayload,
  csrf: string
): Promise<ApiKeyResponse> => {
  return await fetchJson<ApiKeyResponse>(`./api/user/api-keys/${encodeURIComponent(id)}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ ...payload, csrf }),
  });
};

export const deleteApiKey = async (id: number | string, csrf: string): Promise<ApiKeyResponse> => {
  return await fetchJson<ApiKeyResponse>(`./api/user/api-keys/${encodeURIComponent(id)}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ csrf }),
  });
};
