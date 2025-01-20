/**
 * Clear Ratings Handling
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
 * @since     2024-11-14
 */

import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { clearRatings } from '../api';
import { Response } from '../interfaces';

export const handleClearRatings = (): void => {
  const buttonClearRatings = document.getElementById('pmf-admin-clear-ratings') as HTMLButtonElement | null;

  if (buttonClearRatings) {
    buttonClearRatings.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const target = event.target as HTMLElement;
      const csrf = target.getAttribute('data-pmf-csrf')!;
      const response = (await clearRatings(csrf)) as Response;

      if (response.success) {
        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};
