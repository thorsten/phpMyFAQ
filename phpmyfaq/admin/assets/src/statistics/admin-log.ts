/**
 * Handle admin log management
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
 * @since     2024-04-21
 */

import { deleteAdminLog } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const handleDeleteAdminLog = (): void => {
  const buttonDeleteAdminLog = document.getElementById('pmf-delete-admin-log') as HTMLButtonElement | null;

  if (buttonDeleteAdminLog) {
    buttonDeleteAdminLog.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const target = event.target as HTMLElement;
      const csrf = target.getAttribute('data-pmf-csrf');

      if (!csrf) {
        pushErrorNotification('Missing CSRF token');
        return;
      }

      const response = (await deleteAdminLog(csrf)) as Response;

      if (response.success) {
        pushNotification(response.success);
      } else if (response.error) {
        pushErrorNotification(response.error);
      }
    });
  }
};
