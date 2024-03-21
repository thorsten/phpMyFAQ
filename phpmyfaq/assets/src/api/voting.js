/**
 * Private Voting API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

export const saveVoting = async (votingId, votingLanguage, selectedIndex) => {
  try {
    const response = await fetch('api/voting', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: votingId,
        lang: votingLanguage,
        value: selectedIndex,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response.json();
  } catch (error) {
    console.error(error);
  }
};
