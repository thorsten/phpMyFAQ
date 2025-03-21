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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

export const addNews = async (data: Record<string, any> = {}): Promise<void> => {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const deleteNews = async (csrfToken: string, id: string): Promise<void> => {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const updateNews = async (data: Record<string, any> = {}): Promise<void> => {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};

export const activateNews = async (id: string, status: string, csrfToken: string): Promise<void> => {
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

    return await response.json();
  } catch (error) {
    throw error;
  }
};
