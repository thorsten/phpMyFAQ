/**
 * Search Term Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-05-09
 */

import { truncateSearchTerms } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const handleTruncateSearchTerms = (): void => {
  const buttonTruncateSearchTerms = document.getElementById(
    'pmf-button-truncate-search-terms'
  ) as HTMLButtonElement | null;

  if (buttonTruncateSearchTerms) {
    buttonTruncateSearchTerms.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const target = event.target as HTMLElement;
      const csrf = target.getAttribute('data-pmf-csrf-token');

      if (!csrf) {
        pushErrorNotification('Missing CSRF token');
        return;
      }

      if (confirm('Are you sure?')) {
        const response = (await truncateSearchTerms(csrf)) as Response;

        if (response.success) {
          const tableToDelete = document.getElementById('pmf-table-search-terms') as HTMLElement;
          tableToDelete.remove();
          pushNotification(response.success);
        } else if (response.error) {
          pushErrorNotification(response.error);
        }
      }
    });
  }
};
