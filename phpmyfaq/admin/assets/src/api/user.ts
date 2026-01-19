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

export const fetchUsers = async (userName: string): Promise<unknown> => {
  return await fetchJson(`./api/user/users?filter=${userName}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchUserData = async (userId: string): Promise<unknown> => {
  return await fetchJson(`./api/user/data/${userId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchUserRights = async (userId: string): Promise<unknown> => {
  return await fetchJson(`./api/user/permissions/${userId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const fetchAllUsers = async (): Promise<unknown> => {
  return await fetchJson('./api/user/users', {
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
): Promise<unknown> => {
  return await fetchJson('./api/user/overwrite-password', {
    method: 'POST',
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

export const activateUser = async (userId: string, csrfToken: string): Promise<unknown> => {
  return await fetchJson('./api/user/activate', {
    method: 'POST',
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

export const deleteUser = async (userId: string, csrfToken: string): Promise<unknown> => {
  return await fetchJson('./api/user/delete', {
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
