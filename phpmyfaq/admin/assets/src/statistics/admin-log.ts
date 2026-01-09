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

export const handleVerifyAdminLog = async (): Promise<void> => {
  const verifyButton = document.getElementById('pmf-button-verify-admin-log') as HTMLButtonElement;
  const resultContainer = document.getElementById('pmf-admin-log-verification-result') as HTMLDivElement;

  if (!verifyButton || !resultContainer) {
    return;
  }

  verifyButton.addEventListener('click', async (event: Event) => {
    event.preventDefault();

    verifyButton.disabled = true;
    verifyButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';

    resultContainer.classList.add('d-none');

    try {
      const csrfToken = verifyButton.dataset.pmfCsrf;

      if (!csrfToken) {
        throw new Error('CSRF Token not found');
      }

      const response = await fetch(`./api/statistics/admin-log/verify?csrf=${csrfToken}`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
      });

      const data = await response.json();

      resultContainer.classList.remove('d-none');

      if (data.success && data.verification.valid) {
        resultContainer.className = 'alert alert-success';
        resultContainer.innerHTML = `
          <i class="bi bi-check-circle-fill"></i>
          <strong>Integrity verified</strong>
          <p class="mb-0">${data.verification.verified} of ${data.verification.total} entries successfully checked.</p>
        `;
      } else if (data.success && !data.verification.valid) {
        const errors = data.verification.errors.map((err: string) => `<li>${err}</li>`).join('');
        resultContainer.className = 'alert alert-danger';
        resultContainer.innerHTML = `
          <i class="bi bi-exclamation-triangle-fill"></i>
          <strong>⚠️ Manipulation erkannt!</strong>
          <p>${data.verification.verified} verifiziert, ${data.verification.failed} fehlgeschlagen</p>
          <ul>${errors}</ul>
        `;
      } else {
        resultContainer.className = 'alert alert-warning';
        resultContainer.textContent = data.error || 'Fehler bei der Verifikation';
      }
    } catch (error) {
      resultContainer.classList.remove('d-none');
      resultContainer.className = 'alert alert-danger';
      resultContainer.textContent = `Fehler: ${error instanceof Error ? error.message : 'Netzwerkfehler'}`;
    } finally {
      verifyButton.disabled = false;
      verifyButton.innerHTML = '<i class="bi bi-shield-check"></i> Integrität prüfen';
    }
  });
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
