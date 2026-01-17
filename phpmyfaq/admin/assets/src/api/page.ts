/**
 * Fetch data for custom pages
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-15
 */

import { fetchJson } from './fetch-wrapper';

export const addPage = async (data: Record<string, unknown> = {}): Promise<unknown> => {
  return await fetchJson('api/page/create', {
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

export const deletePage = async (csrfToken: string, id: string, lang: string): Promise<unknown> => {
  return await fetchJson('api/page/delete', {
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
      lang: lang,
    }),
  });
};

export const updatePage = async (data: Record<string, unknown> = {}): Promise<unknown> => {
  return await fetchJson('api/page/update', {
    method: 'PUT',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify(data),
  });
};

export const activatePage = async (id: string, status: boolean, csrfToken: string): Promise<unknown> => {
  return await fetchJson('api/page/activate', {
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
};

export const checkSlug = async (
  slug: string,
  lang: string,
  csrfToken: string,
  excludeId?: string
): Promise<unknown> => {
  return await fetchJson('api/page/check-slug', {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify({
      slug: slug,
      lang: lang,
      csrfToken: csrfToken,
      excludeId: excludeId,
    }),
  });
};
