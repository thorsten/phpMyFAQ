/**
 * FAQ administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-07-22
 */

import { deleteAttachments } from '../api';
import { pushNotification, pushErrorNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';
import { Translator } from '../translation/translator';

const showHelp = (option: string): void => {
  const optionHelp = document.getElementById(`${option}Help`) as HTMLElement;
  optionHelp.classList.remove('visually-hidden');
  optionHelp.addEventListener('click', () => (optionHelp.style.opacity = '0'));
  optionHelp.addEventListener('transitionend', () => optionHelp.remove());
};

export const handleFaqForm = (): void => {
  const deleteAttachmentButtons = document.querySelectorAll('.pmf-delete-attachment-button');
  const inputTags = document.getElementById('tags') as HTMLInputElement | null;
  const inputSearchKeywords = document.getElementById('keywords') as HTMLInputElement | null;

  if (inputTags) {
    inputTags.addEventListener('focus', () => showHelp('tags'));
  }
  if (inputSearchKeywords) {
    inputSearchKeywords.addEventListener('focus', () => showHelp('keywords'));
  }
  if (deleteAttachmentButtons) {
    deleteAttachmentButtons.forEach((button) => {
      button.addEventListener('click', async (event: Event) => {
        event.preventDefault();

        const target = event.target as HTMLElement;
        const attachmentId = target.getAttribute('data-pmf-attachment-id') as string;
        const csrfToken = target.getAttribute('data-pmf-csrf-token') as string;

        const response = (await deleteAttachments(attachmentId, csrfToken)) as unknown as Response;

        if (response.success) {
          const listItemToDelete = document.getElementById(`attachment-id-${attachmentId}`) as HTMLElement;
          listItemToDelete.addEventListener('click', () => (listItemToDelete.style.opacity = '0'));
          listItemToDelete.addEventListener('transitionend', () => listItemToDelete.remove());
          pushNotification(response.success);
        }
        if (response.error) {
          pushNotification(response.error);
        }
      });
    });
  }

  const categoryOptions = document.querySelector('#phpmyfaq-categories') as HTMLSelectElement | null;

  if (categoryOptions) {
    Array.from(categoryOptions.selectedOptions).map(({ value }) => value);
    // Override FAQ permissions with Category permission to avoid confused users
    categoryOptions.addEventListener('click', (event) => {
      event.preventDefault();
      const categories = Array.from(categoryOptions.selectedOptions).map(({ value }) => value);
      getCategoryPermissions(categories);
    });
  }

  const questionInput = document.getElementById('question') as HTMLInputElement | null;
  if (questionInput) {
    questionInput.addEventListener('input', checkForHash);
  }
};

const getCategoryPermissions = (categories: string[]): void => {
  fetch(`./api/category/permissions/${categories}`)
    .then((response) => response.json())
    .then((permissions) => {
      setPermissions(permissions);
    });
};

const setPermissions = (permissions: { user: string[]; group: string[] }): void => {
  const perms = permissions;

  // Users
  if (-1 === parseInt(perms.user[0])) {
    (document.getElementById('restrictedusers') as HTMLInputElement).checked = false;
    (document.getElementById('allusers') as HTMLInputElement).checked = true;
  } else {
    (document.getElementById('allusers') as HTMLInputElement).checked = false;
    (document.getElementById('restrictedusers') as HTMLInputElement).checked = true;
    perms.user.forEach((value) => {
      (document.querySelector(`#selected-user option[value='${value}']`) as HTMLOptionElement).selected = true;
    });
  }

  // Groups
  const restrictedGroups = document.getElementById('restrictedgroups') as HTMLInputElement | null;
  if (restrictedGroups) {
    const options = document.querySelectorAll('#restrictedgroups option') as NodeListOf<HTMLOptionElement>;
    const allGroups = document.getElementById('allgroups') as HTMLInputElement | null;
    options.forEach((option) => {
      option.removeAttribute('selected');
    });
    if (-1 === parseInt(perms.group[0])) {
      restrictedGroups.checked = false;
      restrictedGroups.disabled = false;
      if (allGroups) {
        allGroups.checked = true;
        allGroups.disabled = false;
      }
    } else {
      if (allGroups) {
        allGroups.checked = false;
        allGroups.disabled = true;
      }
      restrictedGroups.checked = true;
      restrictedGroups.disabled = false;
      perms.group.forEach((value) => {
        const optionSelected = document.querySelector(
          `#restrictedgroups option[value='${value}']`
        ) as HTMLOptionElement;
        optionSelected.setAttribute('selected', 'selected');
      });
    }
  }
};

const checkForHash = (): void => {
  const questionInputValue = (document.getElementById('question') as HTMLInputElement).value;
  const questionHelp = document.getElementById('questionHelp') as HTMLElement;
  const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement;
  if (questionInputValue.includes('#')) {
    questionHelp.classList.remove('visually-hidden');
    submitButton.setAttribute('disabled', 'true');
  } else {
    questionHelp.classList.add('visually-hidden');
    submitButton.removeAttribute('disabled');
  }
};

export const handleFaqTranslate = (): void => {
  const translateButton = document.getElementById('btn-translate-faq-ai') as HTMLButtonElement | null;
  const langSelect = document.getElementById('lang') as HTMLSelectElement | null;
  const originalLangInput = document.getElementById('originalFaqLang') as HTMLInputElement | null;

  if (!translateButton || !langSelect || !originalLangInput) {
    return;
  }

  // Initialize translator when target language is selected
  langSelect.addEventListener('change', () => {
    const sourceLang = originalLangInput.value;
    const targetLang = langSelect.value;

    if (sourceLang && targetLang && sourceLang !== targetLang) {
      // Enable the translate button
      translateButton.disabled = false;

      // Initialize the Translator
      try {
        new Translator({
          buttonSelector: '#btn-translate-faq-ai',
          contentType: 'faq',
          sourceLang: sourceLang,
          targetLang: targetLang,
          fieldMapping: {
            question: '#question',
            answer: '#editor',
            keywords: '#keywords',
          },
          onTranslationSuccess: () => {
            pushNotification('Translation completed successfully');
          },
          onTranslationError: (error) => {
            pushErrorNotification(`Translation failed: ${error}`);
          },
        });
      } catch (error) {
        console.error('Failed to initialize translator:', error);
      }
    } else {
      // Disable the translate button if same language or no target language
      translateButton.disabled = true;
    }
  });

  // Initially disable the button
  translateButton.disabled = true;
};
