/**
 * Tag Autocomplete API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-12
 */

export const fetchTags = async (searchString) => {
  try {
    const response = await fetch(`./api/content/tags?search=${searchString}`, {
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
    console.error('Error fetching tags:', error);
    if (error.cause && error.cause.response) {
      const errorMessage = await error.cause.response.json();
      console.error(errorMessage);
    }
    throw error;
  }
};

export const deleteTag = async (tagId) => {
  try {
    const response = await fetch(`./api/content/tags/${tagId}`, {
      method: 'DELETE',
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
    console.error('Error deleting tag:', error);
    if (error.cause && error.cause.response) {
      const errorMessage = await error.cause.response.json();
      console.error(errorMessage);
    }
    throw error;
  }
};
