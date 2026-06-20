/**
 * Popular searches API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-20
 */

import { PopularSearch, PopularSearchResponse } from '../interfaces';

/**
 * Validates the raw JSON payload and returns only well-formed entries.
 * The backend is trusted but not guaranteed; malformed items are dropped so they
 * never reach the autocomplete rendering. Numeric fields are coerced to numbers.
 */
const normalizePopularSearches = (data: unknown): PopularSearchResponse => {
  if (!Array.isArray(data)) {
    return [];
  }

  const result: PopularSearch[] = [];

  for (const entry of data) {
    if (typeof entry !== 'object' || entry === null) {
      continue;
    }

    const record = entry as Record<string, unknown>;
    const { searchterm, number } = record;

    if (
      typeof searchterm !== 'string' ||
      searchterm.trim() === '' ||
      (typeof number !== 'number' && typeof number !== 'string')
    ) {
      continue;
    }

    const count = Number(number);
    if (!Number.isFinite(count)) {
      continue;
    }

    result.push({ id: Number(record.id) || 0, searchterm, number: count });
  }

  return result;
};

export const fetchPopularSearches = async (): Promise<PopularSearchResponse> => {
  try {
    const response: Response = await fetch('api/searches/popular', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (!response.ok) {
      return [];
    }

    return normalizePopularSearches(await response.json());
  } catch {
    return [];
  }
};
