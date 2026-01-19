/**
 * Fetch data for statistics management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

import { fetchJson } from './fetch-wrapper';

export const deleteAdminLog = async (csrfToken: string): Promise<unknown> => {
  return await fetchJson(`./api/statistics/admin-log`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const truncateSearchTerms = async (csrfToken: string): Promise<unknown> => {
  return await fetchJson(`./api/statistics/search-terms`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const clearRatings = async (csrfToken: string): Promise<unknown> => {
  return await fetchJson(`./api/statistics/ratings/clear`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const clearVisits = async (csrfToken: string): Promise<unknown> => {
  return await fetchJson(`./api/statistics/visits/clear`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};

export const deleteSessions = async (csrfToken: string, month: string): Promise<unknown> => {
  return await fetchJson(`./api/statistics/sessions`, {
    method: 'DELETE',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrfToken: csrfToken,
      month: month,
    }),
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });
};
