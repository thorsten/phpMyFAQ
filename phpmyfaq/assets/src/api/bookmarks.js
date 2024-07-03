/**
 * Bookmark API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-09-19
 */

export const addBookmark = async (faqId) => {
  try {
    const response = await fetch(`api/bookmark/add/${faqId}`, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
    return await response.json();
  } catch (error) {
    console.error('Error adding bookmark:', error);
  }
}

export const removeBookmark = async (faqId) => {
  try {
    const response = await fetch(`api/bookmark/remove/${faqId}`, {
      method: 'DELETE',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
    return await response.json();
  } catch (error) {
    console.error('Error removing bookmark:', error);
  }
}
