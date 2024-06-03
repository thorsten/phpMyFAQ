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
 * @copyright 2024 phpMyFAQ Team
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
} from '../api/forms';

export const handleFormEdit = () => {
  const forms = document.getElementById('forms');
  if (forms) {
    // Handle activate checkboxes
    document.querySelectorAll('#active').forEach(function (element) {
      element.addEventListener('change', async (event) => {
        const checked = element.checked ? 1 : 0;
        const csrf = element.getAttribute('data-pmf-csrf-token');
        const inputId = element.getAttribute('data-pmf-inputid');
        const formId = element.getAttribute('data-pmf-formid');
        await fetchActivateInput(csrf, formId, inputId, checked);
      });
    });
    // Handle required checkboxes
    document.querySelectorAll('#required').forEach(function (element) {
      element.addEventListener('change', async (event) => {
        const checked = element.checked ? 1 : 0;
        const csrf = element.getAttribute('data-pmf-csrf-token');
        const inputId = element.getAttribute('data-pmf-inputid');
        const formId = element.getAttribute('data-pmf-formid');
        await fetchSetInputAsRequired(csrf, formId, inputId, checked);
      });
    });

    // Handle tabs
    const tabAskQuestion = document.getElementById('ask-question-tab');
    const tabAddContent = document.getElementById('add-content-tab');
    const tabContentAskQuestion = document.getElementById('ask-question');
    const tabContentAddContent = document.getElementById('add-content');

    tabAskQuestion.addEventListener('click', function (event) {
      event.preventDefault();
      tabAskQuestion.classList.add('active');
      tabAddContent.classList.remove('active');
      tabContentAskQuestion.classList.add('active');
      tabContentAddContent.classList.remove('active');
    });
    tabAddContent.addEventListener('click', function (event) {
      event.preventDefault();
      tabAddContent.classList.add('active');
      tabAskQuestion.classList.remove('active');
      tabContentAddContent.classList.add('active');
      tabContentAskQuestion.classList.remove('active');
    });
  }
};

export const handleFormTranslations = () => {
  const table = document.getElementById('formTranslations');
  if (table) {
    // Edit translation
    const editButtons = document.querySelectorAll('#editTranslation');
    editButtons.forEach(function (element) {
      element.addEventListener('click', async () => {
        const lang = element.getAttribute('data-pmf-lang');
        const input = document.getElementById('labelInput_' + lang);
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
          const csrf = element.getAttribute('data-pmf-csrf');
          const formId = element.getAttribute('data-pmf-formId');
          const inputId = element.getAttribute('data-pmf-inputId');
          await fetchEditTranslation(csrf, formId, inputId, lang, input.value);
        }
      });
    });
    // Delete translation
    const deleteButtons = document.querySelectorAll('#deleteTranslation');
    deleteButtons.forEach(function (element) {
      element.addEventListener('click', async () => {
        const csrf = element.getAttribute('data-pmf-csrf');
        const inputId = element.getAttribute('data-pmf-inputId');
        const formId = element.getAttribute('data-pmf-formId');
        const lang = element.getAttribute('data-pmf-lang');
        await fetchDeleteTranslation(csrf, formId, inputId, lang, element);

      });
    });
    // Add Translation
    const addTranslationButton = document.getElementById('addTranslation');
    const languageSelect = document.getElementById('languageSelect');
    const translationInput = document.getElementById('translationText');
    addTranslationButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = addTranslationButton.getAttribute('data-pmf-csrf');
      const inputId = addTranslationButton.getAttribute('data-pmf-inputId');
      const formId = addTranslationButton.getAttribute('data-pmf-formId');
      await fetchAddTranslation(csrf, formId, inputId, languageSelect.value, translationInput.value);
    });
  }
};
