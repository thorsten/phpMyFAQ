/**
 * Fetch data for user management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import { fetchJson } from './fetch-wrapper';
import { AddUserPayload, ApiResponse, UserAutocomplete, UserData, UserEditPayload, UserOverview } from '../interfaces';

// Dual-path endpoint: when a non-empty `filter` query param is sent (as here),
// the server returns autocomplete pairs ({ label, value }); when no filter is
// sent (see fetchAllUsers), it returns full UserOverview objects instead.
export const fetchUsers = async (filter: string): Promise<UserAutocomplete[]> => {
  return await fetchJson<UserAutocomplete[]>(`./api/user/users?filter=${encodeURIComponent(filter)}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchUserData = async (userId: string): Promise<UserData> => {
  return await fetchJson<UserData>(`./api/user/data/${userId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchUserRights = async (userId: string): Promise<string[]> => {
  return await fetchJson<string[]>(`./api/user/permissions/${userId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchAllUsers = async (): Promise<UserOverview[]> => {
  return await fetchJson<UserOverview[]>('./api/user/users', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const overwritePassword = async (
  csrf: string,
  userId: string,
  newPassword: string,
  passwordRepeat: string
): Promise<ApiResponse> => {
  return await fetchJson<ApiResponse>('./api/user/overwrite-password', {
    method: 'PUT',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      userId: userId,
      newPassword: newPassword,
      passwordRepeat: passwordRepeat,
    }),
  });
};

export const postUserData = async (url: string = '', data: Record<string, unknown> = {}): Promise<unknown> => {
  return await fetchJson(url, {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify(data),
  });
};

export const updateUserData = async (payload: UserEditPayload): Promise<ApiResponse> => {
  return await fetchJson<ApiResponse>('./api/user/edit', {
    method: 'PUT',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify(payload),
  });
};

export const updateUserRights = async (
  userId: string,
  userRights: string[],
  csrfToken: string
): Promise<ApiResponse> => {
  return await fetchJson<ApiResponse>('./api/user/update-rights', {
    method: 'PUT',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify({ csrfToken, userId, userRights }),
  });
};

export const addUser = async (payload: AddUserPayload): Promise<ApiResponse | string[]> => {
  return await fetchJson<ApiResponse | string[]>('./api/user/add', {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify(payload),
  });
};

export const activateUser = async (userId: string, csrfToken: string): Promise<ApiResponse> => {
  return await fetchJson<ApiResponse>('./api/user/activate', {
    method: 'PUT',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
      userId: userId,
    }),
  });
};

export const deleteUser = async (userId: string, csrfToken: string): Promise<ApiResponse> => {
  return await fetchJson<ApiResponse>('./api/user/delete', {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify({
      csrfToken: csrfToken,
      userId: userId,
    }),
  });
};
