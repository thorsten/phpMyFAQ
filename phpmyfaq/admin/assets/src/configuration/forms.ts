/**
 * Handle Form edit
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-09
 */

import {
  fetchActivateInput,
  fetchAddTranslation,
  fetchDeleteTranslation,
  fetchEditTranslation,
  fetchSetInputAsRequired,
} from '../api';

export const handleFormEdit = (): void => {
  const forms = document.getElementById('forms');
  if (forms) {
    // Handle activate checkboxes
    document.querySelectorAll<HTMLInputElement>('#active').forEach((element) => {
      element.addEventListener('change', async () => {
        const checked = element.checked;
        const csrf = element.getAttribute('data-pmf-csrf-token') as string;
        const inputId = element.getAttribute('data-pmf-inputid') as string;
        const formId = element.getAttribute('data-pmf-formid') as string;
        await fetchActivateInput(csrf, formId, inputId, checked);
      });
    });
    // Handle required checkboxes
    document.querySelectorAll<HTMLInputElement>('#required').forEach((element) => {
      element.addEventListener('change', async () => {
        const checked = element.checked;
        const csrf = element.getAttribute('data-pmf-csrf-token') as string;
        const inputId = element.getAttribute('data-pmf-inputid') as string;
        const formId = element.getAttribute('data-pmf-formid') as string;
        await fetchSetInputAsRequired(csrf, formId, inputId, checked);
      });
    });

    // Handle tabs
    const tabAskQuestion = document.getElementById('ask-question-tab') as HTMLElement;
    const tabAddContent = document.getElementById('add-content-tab') as HTMLElement;
    const tabContentAskQuestion = document.getElementById('ask-question') as HTMLElement;
    const tabContentAddContent = document.getElementById('add-content') as HTMLElement;

    tabAskQuestion.addEventListener('click', (event) => {
      event.preventDefault();
      tabAskQuestion.classList.add('active');
      tabAddContent.classList.remove('active');
      tabContentAskQuestion.classList.add('active');
      tabContentAddContent.classList.remove('active');
    });
    tabAddContent.addEventListener('click', (event) => {
      event.preventDefault();
      tabAddContent.classList.add('active');
      tabAskQuestion.classList.remove('active');
      tabContentAddContent.classList.add('active');
      tabContentAskQuestion.classList.remove('active');
    });
  }
};

export const handleFormTranslations = (): void => {
  const table = document.getElementById('formTranslations');
  if (table) {
    // Edit translation
    const editButtons = document.querySelectorAll<HTMLElement>('#editTranslation');
    editButtons.forEach((element) => {
      element.addEventListener('click', async () => {
        const lang = element.getAttribute('data-pmf-lang') as string;
        const input = document.getElementById('labelInput_' + lang) as HTMLInputElement;
        if (input.disabled) {
          input.disabled = false;
          element.classList.remove('bg-primary');
          element.classList.add('bg-success');
          element.children[0].classList.remove('bi-pencil');
          element.children[0].classList.add('bi-check');
        } else {
          input.disabled = true;
          element.classList.add('bg-primary');
          element.classList.remove('bg-success');
          element.children[0].classList.add('bi-pencil');
          element.children[0].classList.remove('bi-check');
          const csrf = element.getAttribute('data-pmf-csrf') as string;
          const formId = element.getAttribute('data-pmf-formId') as string;
          const inputId = element.getAttribute('data-pmf-inputId') as string;
          await fetchEditTranslation(csrf, formId, inputId, lang, input.value);
        }
      });
    });
    // Delete translation
    const deleteButtons = document.querySelectorAll<HTMLElement>('#deleteTranslation');
    deleteButtons.forEach((element) => {
      element.addEventListener('click', async () => {
        const csrf = element.getAttribute('data-pmf-csrf') as string;
        const inputId = element.getAttribute('data-pmf-inputId') as string;
        const formId = element.getAttribute('data-pmf-formId') as string;
        const lang = element.getAttribute('data-pmf-lang') as string;
        await fetchDeleteTranslation(csrf, formId, inputId, lang, element);
      });
    });
    // Add Translation
    const addTranslationButton = document.getElementById('addTranslation') as HTMLElement;
    const languageSelect = document.getElementById('languageSelect') as HTMLSelectElement;
    const translationInput = document.getElementById('translationText') as HTMLInputElement;
    addTranslationButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = addTranslationButton.getAttribute('data-pmf-csrf') as string;
      const inputId = addTranslationButton.getAttribute('data-pmf-inputId') as string;
      const formId = addTranslationButton.getAttribute('data-pmf-formId') as string;
      await fetchAddTranslation(csrf, formId, inputId, languageSelect.value, translationInput.value);
    });
  }
};
