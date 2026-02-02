/**
 * Fetch data for Web Push configuration
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
 * @since     2026-02-04
 */

import { fetchJson } from './fetch-wrapper';

export interface VapidKeysResponse {
  success: boolean;
  publicKey: string;
  error?: string;
}

export const fetchGenerateVapidKeys = async (): Promise<VapidKeysResponse> => {
  return (await fetchJson('./api/push/generate-vapid-keys', {
    method: 'POST',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as VapidKeysResponse;
};
