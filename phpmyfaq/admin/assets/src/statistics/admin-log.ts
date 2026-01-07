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

export const handleExportAdminLog = (): void => {
  const buttonExportAdminLog = document.getElementById('pmf-export-admin-log') as HTMLButtonElement | null;

  if (buttonExportAdminLog) {
    buttonExportAdminLog.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const target = event.currentTarget as HTMLElement;
      const csrf = target.getAttribute('data-pmf-csrf');

      if (!csrf) {
        pushErrorNotification('Missing CSRF token');
        return;
      }

      try {
        const response = await fetch('/admin/api/statistics/admin-log/export', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ csrf }),
        });

        if (response.ok) {
          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);
          const contentDisposition = response.headers.get('Content-Disposition');
          const filename = contentDisposition
            ? contentDisposition.split('filename=')[1]?.replace(/"/g, '')
            : 'admin-log-export.csv';

          const a = document.createElement('a');
          a.href = url;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);

          pushNotification('Admin log exported successfully');
        } else {
          const errorData = await response.json();
          pushErrorNotification(errorData.error || 'Export failed');
        }
      } catch (error) {
        pushErrorNotification('Export error: ' + error);
      }
    });
  }
};

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
