/**
 * Fetch data for exports
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
 * @since     2024-05-05
 */

import { Response } from '../interfaces';

export const createReport = async (data: unknown, csrfToken: string): Promise<Blob | Response | undefined> => {
  try {
    const response = await fetch('./api/export/report', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data: data,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.ok) {
      return await response.blob();
    } else {
      return await response.json();
    }
  } catch (error) {
    console.error(error);
  }
};
