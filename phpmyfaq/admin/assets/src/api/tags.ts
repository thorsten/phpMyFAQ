/**
 * Tag Autocomplete API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-12
 */

import { fetchJson } from './fetch-wrapper';

interface TagResponse {
  id: string;
  name: string;
}

export const fetchTags = async (searchString: string): Promise<TagResponse[]> => {
  return await fetchJson(`./api/content/tags?search=${searchString}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const deleteTag = async (tagId: string): Promise<{ success?: string; error?: string }> => {
  return await fetchJson(`./api/content/tags/${tagId}`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};
