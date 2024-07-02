/**
 * Fetch data for news
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */
import { pushErrorNotification, pushNotification } from '../utils';

export const addNews = async (data = {}) => {
  try {
    const response = await fetch('api/news/create', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data),
    });

    const result = await response.json();
    if (result.success) {
      pushNotification(result.success);
      setTimeout(function () {
        window.location.href = '?action=news';
      }, 3000);
    } else {
      pushErrorNotification(result.error);
    }
  } catch (error) {
    console.error('Error posting news data:', error);
    throw error;
  }
};

export const deleteNews = async (csrfToken, id) => {
  try {
    const response = await fetch('api/news/delete', {
      method: 'DELETE',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify({
        csrfToken: csrfToken,
        id: id,
      }),
    });

    const result = await response.json();
    if (result.success) {
      pushNotification(result.success);
      setTimeout(function () {
        window.location.reload();
      }, 3000);
    } else {
      pushErrorNotification(result.error);
    }
  } catch (error) {
    console.error('Error deleting news data:', error);
    throw error;
  }
};

export const updateNews = async (data = {}) => {
  try {
    const response = await fetch('api/news/update', {
      method: 'PUT',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data),
    });

    const result = await response.json();
    if (result.success) {
      pushNotification(result.success);
      setTimeout(function () {
        window.location.href = '?action=news';
      }, 3000);
    } else {
      pushErrorNotification(result.error);
    }
  } catch (error) {
    console.error('Error posting news data:', error);
    throw error;
  }
};

export const activateNews = async (id, status, csrfToken) => {
  try {
    const response = await fetch('api/news/activate', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify({
        id: id,
        status: status,
        csrfToken: csrfToken,
      }),
    });

    const result = await response.json();
    if (result.success) {
      pushNotification(result.success);
    } else {
      pushErrorNotification(result.error);
    }
  } catch (error) {
    console.error('Error posting news data:', error);
    throw error;
  }
};
