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

export const fetchUsers = async (userName) => {
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
      throw new Error('Network response was not ok: ', { cause: { response } });
    }
  } catch (error) {
    console.error('Error fetching users:', error);
    if (error.cause && error.cause.response) {
      const errorMessage = await error.cause.response.json();
      console.error(errorMessage.error);
    }
    throw error;
  }
};

export const fetchUserData = async (userId) => {
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

export const fetchUserRights = async (userId) => {
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

export const fetchAllUsers = async () => {
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

export const overwritePassword = async (csrf, userId, newPassword, passwordRepeat) => {
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

export const postUserData = async (url = '', data = {}) => {
  try {
    return await fetch(url, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data),
    });
  } catch (error) {
    console.error('Error posting user data:', error);
    throw error;
  }
};

export const deleteUser = async (userId, csrfToken) => {
  try {
    return await fetch('./api/user/delete', {
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
  } catch (error) {
    console.error('Error posting user data:', error);
    throw error;
  }
};
