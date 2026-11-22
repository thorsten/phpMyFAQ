/**
 * Shared handling of JSON responses of the public API
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
 * @since     2026-11-21
 */

/**
 * The controllers answer a refused request with a non-2xx status and a JSON body
 * `{ error: '<translated message>' }`. That message is what the user has to see,
 * so it becomes the message of the thrown error; `HTTP <status>` is only the
 * fallback for responses without one.
 */
const readErrorMessage = async (response: Response): Promise<string> => {
  const fallback = `HTTP ${response.status}`;

  try {
    const body: unknown = await response.json();
    const error = (body as { error?: unknown } | null)?.error;

    return typeof error === 'string' && error.trim() !== '' ? error.trim() : fallback;
  } catch {
    return fallback;
  }
};

export const parseJsonResponse = async <T>(response: Response): Promise<T> => {
  if (!response.ok) {
    throw new Error(await readErrorMessage(response));
  }

  return await response.json();
};
