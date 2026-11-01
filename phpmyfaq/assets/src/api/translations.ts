/**
 * Private Translations API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-03-17
 */

/**
 * @param apiBasePath Path of the public API relative to the document's <base href>;
 *                    the admin pages live one directory deeper and pass '../api'.
 */
export const fetchTranslations = async (locale: string, apiBasePath = 'api'): Promise<Record<string, string>> => {
  const response: Response = await fetch(`${apiBasePath}/translations/${locale}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return response.json();
};
