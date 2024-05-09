/**
 * Search Term Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-05-09
 */

import { truncateSearchTerms } from '../api';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleTruncateSearchTerms = () => {
  const buttonTruncateSearchTerms = document.getElementById('pmf-button-truncate-search-terms');

  if (buttonTruncateSearchTerms) {
    buttonTruncateSearchTerms.addEventListener('click', async (event) => {
      event.preventDefault();

      const csrf = event.target.getAttribute('data-pmf-csrf-token');

      if (confirm('Are you sure?')) {
        const response = await truncateSearchTerms(csrf);

        if (response.success) {
          const tableToDelete = document.getElementById('pmf-table-search-terms');
          tableToDelete.remove();
          pushNotification(response.success);
        } else {
          pushErrorNotification(response.error);
        }
      }
    });
  }
};
