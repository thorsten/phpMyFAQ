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
import { pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const handleFormEdit = (): void => {
  const forms = document.getElementById('forms') as HTMLElement;
  if (forms) {
    // Handle activate checkboxes
    document.querySelectorAll<HTMLInputElement>('#active').forEach((element: HTMLInputElement): void => {
      element.addEventListener('change', async (): Promise<void> => {
        const checked: boolean = element.checked;
        const csrf = element.getAttribute('data-pmf-csrf-token') as string;
        const inputId = element.getAttribute('data-pmf-inputid') as string;
        const formId = element.getAttribute('data-pmf-formid') as string;
        const response = (await fetchActivateInput(csrf, formId, inputId, checked)) as unknown as Response;
        if (typeof response.success === 'string') {
          pushNotification(response.success);
        } else {
          console.error(response.error);
        }
      });
    });

    // Handle required checkboxes
    document.querySelectorAll<HTMLInputElement>('#required').forEach((element: HTMLInputElement): void => {
      element.addEventListener('change', async (): Promise<void> => {
        const checked: boolean = element.checked;
        const csrf = element.getAttribute('data-pmf-csrf-token') as string;
        const inputId = element.getAttribute('data-pmf-inputid') as string;
        const formId = element.getAttribute('data-pmf-formid') as string;
        const response = (await fetchSetInputAsRequired(csrf, formId, inputId, checked)) as unknown as Response;
        if (typeof response.success === 'string') {
          pushNotification(response.success);
        } else {
          console.error(response.error);
        }
      });
    });

    // Handle tabs
    const tabAskQuestion = document.getElementById('ask-question-tab') as HTMLElement;
    const tabAddContent = document.getElementById('add-content-tab') as HTMLElement;
    const tabContentAskQuestion = document.getElementById('ask-question') as HTMLElement;
    const tabContentAddContent = document.getElementById('add-content') as HTMLElement;

    if (tabAskQuestion) {
      tabAskQuestion.addEventListener('click', (event: Event): void => {
        event.preventDefault();
        tabAskQuestion.classList.add('active');
        tabAddContent.classList.remove('active');
        tabContentAskQuestion.classList.add('active');
        tabContentAddContent.classList.remove('active');
      });
    }

    if (tabAddContent) {
      tabAddContent.addEventListener('click', (event: Event): void => {
        event.preventDefault();
        tabAddContent.classList.add('active');
        tabAskQuestion.classList.remove('active');
        tabContentAddContent.classList.add('active');
        tabContentAskQuestion.classList.remove('active');
      });
    }
  }
};

export const handleFormTranslations = (): void => {
  const table = document.getElementById('formTranslations') as HTMLElement;
  if (table) {
    // Edit translation
    const editButtons: NodeListOf<HTMLElement> = document.querySelectorAll<HTMLElement>('#editTranslation');
    editButtons.forEach((element: HTMLElement): void => {
      element.addEventListener('click', async (): Promise<void> => {
        const lang = element.getAttribute('data-pmf-lang') as string;
        const input = document.getElementById('labelInput_' + lang) as HTMLInputElement;
        if (input.disabled) {
          input.disabled = false;
          element.classList.remove('bg-primary');
          element.classList.add('bg-success');
          if (element.children[0]) {
            element.children[0].classList.remove('bi-check');
            element.children[0].classList.add('bi-pencil');
          }
        } else {
          input.disabled = true;
          element.classList.add('bg-primary');
          element.classList.remove('bg-success');
          if (element.children[0]) {
            element.children[0].classList.add('bi-pencil');
            element.children[0].classList.remove('bi-check');
          }
          const csrf = element.getAttribute('data-pmf-csrf') as string;
          const formId = element.getAttribute('data-pmf-formId') as string;
          const inputId = element.getAttribute('data-pmf-inputId') as string;
          const response = (await fetchEditTranslation(
            csrf,
            formId,
            inputId,
            lang,
            input.value
          )) as unknown as Response;
          if (typeof response.success === 'string') {
            pushNotification(response.success);
          } else {
            console.error(response.error);
          }
        }
      });
    });
    // Delete translation
    const deleteButtons: NodeListOf<HTMLElement> = document.querySelectorAll<HTMLElement>('#deleteTranslation');
    deleteButtons.forEach((element: HTMLElement) => {
      element.addEventListener('click', async (): Promise<void> => {
        const csrf = element.getAttribute('data-pmf-csrf') as string;
        const inputId = element.getAttribute('data-pmf-inputId') as string;
        const formId = element.getAttribute('data-pmf-formId') as string;
        const lang = element.getAttribute('data-pmf-lang') as string;
        const response = (await fetchDeleteTranslation(csrf, formId, inputId, lang)) as unknown as Response;
        if (typeof response.success === 'string') {
          pushNotification(response.success);
          document.getElementById('item_' + lang)?.remove();
          const option = document.createElement('option') as HTMLOptionElement;
          option.innerText = element.getAttribute('data-pmf-langname')!;
          document.getElementById('languageSelect')?.appendChild(option);
        } else {
          console.error(response.error);
        }
      });
    });
    // Add Translation
    const addTranslationButton = document.getElementById('addTranslation') as HTMLElement;
    const languageSelect = document.getElementById('languageSelect') as HTMLSelectElement;
    const translationInput = document.getElementById('translationText') as HTMLInputElement;

    if (addTranslationButton) {
      addTranslationButton.addEventListener('click', async (event: Event): Promise<void> => {
        event.preventDefault();
        const csrf = addTranslationButton.getAttribute('data-pmf-csrf') as string;
        const inputId = addTranslationButton.getAttribute('data-pmf-inputId') as string;
        const formId = addTranslationButton.getAttribute('data-pmf-formId') as string;
        const response = (await fetchAddTranslation(
          csrf,
          formId,
          inputId,
          languageSelect.value,
          translationInput.value
        )) as unknown as Response;
        if (typeof response.success === 'string') {
          pushNotification(response.success);
          setTimeout((): void => {
            window.location.reload();
          }, 3000);
        } else {
          console.error(response.error);
        }
      });
    }
  }
};
