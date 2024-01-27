/**
 * Session Handling
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
 * @since     2024-01-14
 */

import { pushErrorNotification } from '../utils';

export const handleSessions = () => {
  const firstHour = document.getElementById('firstHour');
  const lastHour = document.getElementById('lastHour');
  const exportSessions = document.getElementById('exportSessions');
  const csrf = document.getElementById('csrf');

  if (firstHour && lastHour) {
    firstHour.addEventListener('change', async () => {
      exportSessions.disabled = !(firstHour.value !== '' && lastHour.value !== '');
    });
    lastHour.addEventListener('change', async () => {
      exportSessions.disabled = !(lastHour.value !== '' && firstHour.value !== '');
    });
  }

  if (exportSessions) {
    exportSessions.disabled = true;
    exportSessions.addEventListener('click', async (event) => {
      event.preventDefault();

      try {
        const response = await fetch('./api/session/export', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf: csrf.value,
            firstHour: firstHour.value,
            lastHour: lastHour.value,
          }),
        });
        if (response.ok) {
          const blob = await response.blob();
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = 'sessions_' + firstHour.value + '--' + lastHour.value + '.csv';
          document.body.appendChild(link);
          link.click();
          URL.revokeObjectURL(url);
        } else {
          const jsonResponse = response.json();
          pushErrorNotification(jsonResponse.error);
        }
      } catch (error) {
        console.error(error.message);
      }
    });
  }
};
