/**
 * Autocomplete API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

import { AutocompleteSearchResponse } from '../interfaces';

export const fetchAutoCompleteData = async (searchString: string): Promise<AutocompleteSearchResponse> => {
  const response: Response = await fetch(`api/autocomplete?search=${searchString}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error('Network response was not ok.');
  }

  return await response.json();
};
