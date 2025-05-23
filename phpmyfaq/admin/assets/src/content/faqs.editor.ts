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
import { pushErrorNotification, pushNotification, serialize } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

interface SerializedData {
  faqId: string;
  [key: string]: any;
}

export const handleSaveFaqData = (): void => {
  const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement | null;

  if (submitButton) {
    submitButton.addEventListener('click', async (event: Event) => {
      event.preventDefault();
      const form = document.getElementById('faqEditor') as HTMLFormElement;
      const formData = new FormData(form);

      const serializedData = serialize(formData) as SerializedData;

      let response: Response | undefined;
      if (serializedData.faqId === '0') {
        response = await create(serializedData);
      } else {
        response = await update(serializedData);
      }

      if (response?.success) {
        const data = JSON.parse(response.data);
        const faqId = document.getElementById('faqId') as HTMLInputElement;
        const revisionId = document.getElementById('revisionId') as HTMLInputElement;

        faqId.value = data.id;
        revisionId.value = data.revisionId;

        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleUpdateQuestion = (): void => {
  const input = document.getElementById('question') as HTMLInputElement | null;
  if (input) {
    input.addEventListener('input', () => {
      const output = document.getElementById('pmf-admin-question-output') as HTMLElement;
      output.innerText = `: ${input.value}`;
    });
  }
};
