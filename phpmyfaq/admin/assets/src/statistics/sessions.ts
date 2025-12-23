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

import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { clearVisits, deleteSessions } from '../api';

export const handleSessionsFilter = (): void => {
  const button = document.getElementById('pmf-admin-session-day') as HTMLButtonElement | null;

  if (button) {
    button.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const form = document.getElementById('pmf-admin-form-session') as HTMLFormElement;
      const timestamp = (document.getElementById('day') as HTMLInputElement).value;
      const day = new Date(Number(timestamp) * 1000).toISOString().split('T')[0];
      form.action = `./statistics/sessions/${day}`;
      form.submit();
    });
  }
};

export const handleSessions = (): void => {
  const firstHour = document.getElementById('firstHour') as HTMLInputElement | null;
  const lastHour = document.getElementById('lastHour') as HTMLInputElement | null;
  const exportSessions = document.getElementById('exportSessions') as HTMLButtonElement | null;
  const csrf = document.getElementById('csrf') as HTMLInputElement | null;

  if (firstHour && lastHour) {
    firstHour.addEventListener('change', () => {
      if (exportSessions) {
        exportSessions.disabled = !(firstHour.value !== '' && lastHour.value !== '');
      }
    });
    lastHour.addEventListener('change', () => {
      if (exportSessions) {
        exportSessions.disabled = !(lastHour.value !== '' && firstHour.value !== '');
      }
    });
  }

  if (exportSessions) {
    exportSessions.disabled = true;
    exportSessions.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      try {
        const response = await fetch('./api/session/export', {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf: csrf?.value,
            firstHour: firstHour?.value,
            lastHour: lastHour?.value,
          }),
        });
        if (response.ok) {
          const blob = await response.blob();
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = `sessions_${firstHour?.value}--${lastHour?.value}.csv`;
          document.body.appendChild(link);
          link.click();
          URL.revokeObjectURL(url);
        } else {
          const jsonResponse = await response.json();
          pushErrorNotification(jsonResponse.error);
        }
      } catch (error: unknown) {
        const errorMessage = error instanceof Error ? error.message : 'An error occurred during export';
        console.error(errorMessage);
        pushErrorNotification(errorMessage);
      }
    });
  }
};

export const handleClearVisits = (): void => {
  const buttonClearVisits = document.getElementById('pmf-admin-clear-visits') as HTMLButtonElement | null;

  if (buttonClearVisits) {
    buttonClearVisits.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const target = event.target as HTMLElement;
      const csrf = target.getAttribute('data-pmf-csrf');

      if (!csrf) {
        pushErrorNotification('Missing CSRF token');
        return;
      }

      const response = await clearVisits(csrf);

      if (!response) {
        pushErrorNotification('No response received');
        return;
      }

      if (response.success) {
        pushNotification(response.success);
      } else if (response.error) {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleDeleteSessions = (): void => {
  const buttonDeleteSessions = document.getElementById('pmf-admin-delete-sessions') as HTMLButtonElement | null;

  if (buttonDeleteSessions) {
    buttonDeleteSessions.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const csrf = (document.getElementById('pmf-csrf-token') as HTMLInputElement).value;
      const month = (document.getElementById('month') as HTMLInputElement).value;
      const response = await deleteSessions(csrf, month);

      if (!response) {
        pushErrorNotification('No response received');
        return;
      }

      if (response.success) {
        pushNotification(response.success);
      } else if (response.error) {
        pushErrorNotification(response.error);
      }
    });
  }
};
