/**
 * Captcha API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-12
 */

export const fetchCaptchaImage = async (action: string, timestamp: number): Promise<Response | undefined> => {
  try {
    const response: Response = await fetch('api/captcha', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify({
        action,
        timestamp,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response;
  } catch (error) {
    console.error(error);
  }
};
