/**
 * Fetch data for verification
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
 * @since     2024-07-09
 */

import { fetchJson } from './fetch-wrapper';

interface RemoteHashes {
  [key: string]: string;
}

interface VerificationResult {
  [filename: string]: string;
}

export const getRemoteHashes = async (version: string): Promise<RemoteHashes | undefined> => {
  return (await fetchJson(`https://api.phpmyfaq.de/verify/${version}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  })) as RemoteHashes | undefined;
};

export const verifyHashes = async (remoteHashes: RemoteHashes): Promise<VerificationResult> => {
  return (await fetchJson('./api/dashboard/verify', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(remoteHashes),
  })) as VerificationResult;
};
