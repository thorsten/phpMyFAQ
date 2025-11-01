/**
 * Fetch data for user management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-23
 */

import { Response, UserData } from '../interfaces';

export const fetchUsers = async (userName: string): Promise<Response> => {
  try {
    const response = await fetch(`./api/user/users?filter=${userName}`, {
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

export const fetchUserData = async (userId: string): Promise<UserData> => {
  try {
    const response = await fetch(`./api/user/data/${userId}`, {
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

export const fetchUserRights = async (userId: string): Promise<number[]> => {
  try {
    const response = await fetch(`./api/user/permissions/${userId}`, {
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

export const fetchAllUsers = async (): Promise<Response> => {
  try {
    const response = await fetch('./api/user/users', {
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

export const overwritePassword = async (
  csrf: string,
  userId: string,
  newPassword: string,
  passwordRepeat: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch('./api/user/overwrite-password', {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const postUserData = async (url: string = '', data: Record<string, any> = {}): Promise<Response> => {
  try {
    const response = await fetch(url, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data),
    });

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const activateUser = async (userId: string, csrfToken: string): Promise<Response> => {
  try {
    const response = await fetch('./api/user/activate', {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const deleteUser = async (userId: string, csrfToken: string): Promise<Response> => {
  try {
    const response = await fetch('./api/user/delete', {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};
