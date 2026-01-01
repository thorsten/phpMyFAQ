/**
 * Stuff for importing records via csv-file
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

import { pushNotification, pushErrorNotification } from '../../../../assets/src/utils';

interface ImportError {
  storedAll?: boolean;
  messages?: string[];
}

export const handleUploadCSVForm = async (): Promise<void> => {
  const submitButton = document.getElementById('submitButton') as HTMLButtonElement | null;
  if (submitButton) {
    submitButton.addEventListener('click', async (event: Event) => {
      event.preventDefault();
      const fileInput = document.getElementById('fileInputCSVUpload') as HTMLInputElement;
      const form = document.getElementById('uploadCSVFileForm') as HTMLFormElement;
      const csrf = form.getAttribute('data-pmf-csrf') as string;
      const file = fileInput.files?.[0];
      if (!file) {
        pushErrorNotification('No file selected.');
        return;
      }
      const formData = new FormData();
      formData.append('file', file);
      formData.append('csrf', csrf);
      try {
        const response = await fetch('./api/faq/import', {
          method: 'POST',
          body: formData,
        });
        if (response.ok) {
          const jsonResponse = await response.json();
          pushNotification(jsonResponse.success);
          fileInput.value = '';
        } else if (response.status === 400) {
          const jsonResponse = await response.json();
          pushErrorNotification(jsonResponse.error);
          fileInput.value = '';
        } else {
          const errorResponse = await response.json();
          console.error('Network response was not ok:', JSON.stringify(errorResponse));
          pushErrorNotification('Network response was not ok: ' + JSON.stringify(errorResponse));
        }
      } catch (error: unknown) {
        const importError = error as ImportError;
        if (importError.storedAll === false && importError.messages) {
          importError.messages.forEach((message: string) => {
            pushErrorNotification(message);
          });
        } else {
          console.error('An error occurred:', error);
          pushErrorNotification('An error occurred during import');
        }
      }
    });
  }
};
