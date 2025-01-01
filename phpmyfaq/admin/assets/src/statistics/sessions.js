/**
 * Session Handling
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
 * @since     2024-01-14
 */

import { pushErrorNotification, pushNotification } from '../utils';
import { clearVisits, deleteSessions } from '../api/index.js';

export const handleSessionsFilter = () => {
  const button = document.getElementById('pmf-admin-session-day');

  if (button) {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      const form = document.getElementById('pmf-admin-form-session');
      const timestamp = document.getElementById('day').value;
      const day = new Date(timestamp * 1000).toISOString().split('T')[0];
      form.action = `./statistics/sessions/${day}`;
      form.submit();
    });
  }
};

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

export const handleClearVisits = () => {
  const buttonClearRatings = document.getElementById('pmf-admin-clear-visits');

  if (buttonClearRatings) {
    buttonClearRatings.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = event.target.getAttribute('data-pmf-csrf');
      const response = await clearVisits(csrf);

      if (response.success) {
        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleDeleteSessions = () => {
  const buttonClearRatings = document.getElementById('pmf-admin-delete-sessions');

  if (buttonClearRatings) {
    buttonClearRatings.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = document.getElementById('pmf-csrf-token').value;
      const month = document.getElementById('month').value;
      const response = await deleteSessions(csrf, month);

      if (response.success) {
        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};
