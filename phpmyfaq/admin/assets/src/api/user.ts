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

import { Response } from '../interfaces';

export const fetchUsers = async (userName: string): Promise<Response | undefined> => {
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

    if (response.ok) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error fetching users:', error);
  }
};

export const fetchUserData = async (userId: string): Promise<Response | undefined> => {
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

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error(`Error fetching data for user ${userId}:`, error);
    throw error;
  }
};

export const fetchUserRights = async (userId: string): Promise<Response | undefined> => {
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

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error(`Error fetching permissions for user ${userId}:`, error);
    throw error;
  }
};

export const fetchAllUsers = async (): Promise<Response | undefined> => {
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

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error fetching all users:', error);
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

    if (response.status === 200 || response.status === 400 || response.status === 401) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error overwriting user password:', error);
    throw error;
  }
};

export const postUserData = async (url: string = '', data: Record<string, any> = {}): Promise<Response | undefined> => {
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
    console.error('Error posting user data:', error);
    throw error;
  }
};

export const deleteUser = async (userId: string, csrfToken: string): Promise<Response | undefined> => {
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
    console.error('Error deleting user:', error);
    throw error;
  }
};
