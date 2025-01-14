/**
 * Fetch data for verification
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-09
 */

export const getRemoteHashes = async (version) => {
  try {
    const response = await fetch(`https://api.phpmyfaq.de/verify/${version}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error getting verification data from api.phpmyfaq.de: ', error);
    throw error;
  }
};

export const verifyHashes = async (remoteHashes) => {
  try {
    const response = await fetch('./api/dashboard/verify', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(remoteHashes),
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};
