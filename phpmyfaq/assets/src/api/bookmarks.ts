/**
 * Bookmark API functionality
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
 * @since     2023-09-19
 */

import { BookmarkResponse } from '../interfaces';

export const createBookmark = async (faqId: string, csrf: string): Promise<BookmarkResponse> => {
  const response: Response = await fetch(`api/bookmark/create`, {
    method: 'POST',
    cache: 'no-cache',
    body: JSON.stringify({
      id: faqId,
      csrfToken: csrf,
    }),
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const deleteBookmark = async (faqId: string, csrf: string): Promise<BookmarkResponse> => {
  const response: Response = await fetch(`api/bookmark/delete`, {
    method: 'DELETE',
    cache: 'no-cache',
    body: JSON.stringify({
      id: faqId,
      csrfToken: csrf,
    }),
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const deleteAllBookmarks = async (csrf: string): Promise<BookmarkResponse> => {
  const response: Response = await fetch(`api/bookmark/delete-all`, {
    method: 'DELETE',
    cache: 'no-cache',
    body: JSON.stringify({
      csrfToken: csrf,
    }),
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};
