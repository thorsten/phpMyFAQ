/**
 * Clear Ratings Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-14
 */

import { pushErrorNotification, pushNotification } from '../utils/index.js';
import { clearRatings } from '../api/index.js';

export const handleClearRatings = () => {
  const buttonClearRatings = document.getElementById('pmf-admin-clear-ratings');

  if (buttonClearRatings) {
    buttonClearRatings.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = event.target.getAttribute('data-pmf-csrf');
      const response = await clearRatings(csrf);

      if (response.success) {
        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};
