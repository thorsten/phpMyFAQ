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
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

import { addElement } from '../../../../assets/src/utils';
import { pushNotification, pushErrorNotification } from '../utils';

export const handleUploadCSVForm = async () => {
  const submitButton = document.getElementById('submitButton');
  if (submitButton) {
    submitButton.addEventListener('click', async (event) => {
      const fileInput = document.getElementById('fileInputCSVUpload');
      const form = document.getElementById('uploadCSVFileForm');
      const csrf = form.getAttribute('data-pmf-csrf');
      const file = fileInput.files[0];
      event.preventDefault();
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
          fileInput.value = null;
        }
        if (response.status === 400) {
          const jsonResponse = await response.json();
          pushErrorNotification(jsonResponse.error);
          fileInput.value = null;
        } else {
          const errorResponse = await response.json();
          throw new Error('Network response was not ok: ' + JSON.stringify(errorResponse));
        }
      } catch (error) {
        if (error.storedAll === false) {
          error.messages.forEach((message) => {
            pushErrorNotification(message);
          });
        }
      }
    });
  }
};
