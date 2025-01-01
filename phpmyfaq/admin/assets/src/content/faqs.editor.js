/**
 * FAQ edit handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

import { create, update } from '../api';
import { serialize } from '../../../../assets/src/utils';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleSaveFaqData = () => {
  const submitButton = document.getElementById('faqEditorSubmit');

  if (submitButton) {
    submitButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const form = document.getElementById('faqEditor');
      const formData = new FormData(form);

      const serializedData = serialize(formData);

      let response;
      if (serializedData.faqId === '0') {
        response = await create(serializedData);
      } else {
        response = await update(serializedData);
      }

      if (response.success) {
        const data = JSON.parse(response.data);
        const faqId = document.getElementById('faqId');
        const revisionId = document.getElementById('revisionId');

        faqId.value = data.id;
        revisionId.value = data.revisionId;

        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleUpdateQuestion = () => {
  const input = document.getElementById('question');
  if (input) {
    input.addEventListener('input', () => {
      document.getElementById('pmf-admin-question-output').innerText = `: ${input.value}`;
    });
  }
};
