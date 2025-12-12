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

import { create, update, deleteFaq } from '../api';
import { pushErrorNotification, pushNotification, serialize } from '../../../../assets/src/utils';
import { Response } from '../interfaces';
import { getJoditEditor } from './editor';

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
        const data = response.data ? JSON.parse(response.data) : {};
        const faqId = document.getElementById('faqId') as HTMLInputElement;
        const revisionId = document.getElementById('revisionId') as HTMLInputElement;

        faqId.value = data.id;
        revisionId.value = data.revisionId;

        pushNotification(response.success);
      } else {
        if (response && response.error) {
          pushErrorNotification(response.error);
        }
      }
    });
  }
};

export const handleDeleteFaqEditorModal = (): void => {
  const deleteButton = document.getElementById('faqEditorDelete') as HTMLButtonElement | null;
  const confirmDeleteButton = document.getElementById('pmf-confirm-delete-faq') as HTMLButtonElement | null;

  if (!deleteButton || !confirmDeleteButton) {
    return;
  }

  confirmDeleteButton.addEventListener('click', async (event: Event): Promise<void> => {
    event.preventDefault();

    const faqId = deleteButton.getAttribute('data-faq-id') as string;
    const faqLanguage = deleteButton.getAttribute('data-faq-language') as string;
    const csrfToken = deleteButton.getAttribute('data-pmf-csrf-token') as string;

    if (!faqId || !faqLanguage || !csrfToken) {
      pushErrorNotification('Fehlende Parameter zum Löschen der FAQ.');
      return;
    }

    try {
      const response = await deleteFaq(faqId, faqLanguage, csrfToken);

      if (response?.success) {
        pushNotification(response.success);
        // Nach kurzer Verzögerung zur FAQ-Übersicht umleiten
        window.setTimeout(() => {
          window.location.href = './faqs';
        }, 1000);
      } else if (response?.error) {
        pushErrorNotification(response.error);
      } else {
        pushErrorNotification('Beim Löschen der FAQ ist ein unbekannter Fehler aufgetreten.');
      }
    } catch (error) {
      console.error(error);
      pushErrorNotification('Beim Löschen der FAQ ist ein Fehler aufgetreten.');
    }
  });
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

export const handleResetButton = (): void => {
  const resetButton = document.querySelector('button[type="reset"]') as HTMLButtonElement | null;
  if (resetButton) {
    resetButton.addEventListener('click', (event: Event): void => {
      event.preventDefault();
      const form = document.getElementById('faqEditor') as HTMLFormElement;

      // Store original values before reset
      const editorTextarea = document.getElementById('editor') as HTMLTextAreaElement | null;
      const markdownTextarea = document.getElementById('answer-markdown') as HTMLTextAreaElement | null;
      const questionInput = document.getElementById('question') as HTMLInputElement | null;
      const questionOutput = document.getElementById('pmf-admin-question-output') as HTMLElement | null;
      const originalEditorContent = editorTextarea?.defaultValue || '';
      const originalMarkdownContent = markdownTextarea?.defaultValue || '';
      const originalQuestionValue = questionInput?.defaultValue || '';

      // Reset the form
      form.reset();

      // Reset Jodit editor if it exists
      const joditEditor = getJoditEditor();
      if (joditEditor && editorTextarea) {
        joditEditor.value = originalEditorContent;
      }

      // Reset markdown editor if it exists
      if (markdownTextarea) {
        markdownTextarea.value = originalMarkdownContent;
      }

      // Reset question output span
      if (questionOutput && originalQuestionValue) {
        questionOutput.innerText = `: ${originalQuestionValue}`;
      } else if (questionOutput) {
        questionOutput.innerText = '';
      }

      // Handle revision select dropdown
      const revisionSelect = document.getElementById('selectedRevisionId') as HTMLSelectElement | null;
      if (revisionSelect && revisionSelect.options.length > 0) {
        const lastOption = revisionSelect.options[revisionSelect.options.length - 1] as HTMLOptionElement;
        revisionSelect.value = lastOption.value;
        revisionSelect.dispatchEvent(new Event('change'));
      }
    });
  }
};
