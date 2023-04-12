/**
 * Tag Autocomplete API functionality
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-12
 */

export const fetchTags = async (searchString) => {
  return await fetch(`index.php?action=ajax&ajax=tags&ajaxaction=list&search=${searchString}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })
    .then(async (response) => {
      if (response.status === 200) {
        return response.json();
      }
      throw new Error('Network response was not ok.');
    })
    .then((response) => {
      return response;
    });
};
