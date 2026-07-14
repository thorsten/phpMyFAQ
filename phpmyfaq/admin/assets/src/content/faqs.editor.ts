/**
 * FAQ edit handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

import { create, update, deleteFaq } from '../api';
import { pushErrorNotification, pushNotification, serialize } from '../../../../assets/src/utils';
import { ApiResponse } from '../interfaces';
import { getJoditEditor } from './editor';
import { analyzeReadability, SupportedLanguage } from '../utils';
import { markClean } from './faqs.editor.state';
import {
  applyValidationFeedback,
  getAnswerContent,
  showFirstValidationError,
  validateFaqEditor,
} from './faqs.editor.validation';

interface SerializedData {
  faqId: string;
  [key: string]: FormDataEntryValue | FormDataEntryValue[];
}

const setSaveButtonLoading = (button: HTMLButtonElement, loading: boolean): void => {
  if (loading) {
    // Only cache when not already loading, so a re-entrant call cannot
    // capture the spinner markup as the "original" label.
    button.dataset.pmfOriginalMarkup ??= button.innerHTML;
    button.disabled = true;
    const savingLabel = button.getAttribute('data-pmf-label-saving') ?? '';
    button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${savingLabel}`;
  } else {
    button.innerHTML = button.dataset.pmfOriginalMarkup ?? button.innerHTML;
    delete button.dataset.pmfOriginalMarkup;
    button.disabled = false;
  }
};

const showSavedIndicator = (button: HTMLButtonElement): void => {
  const indicator = document.getElementById('pmf-faq-saved-indicator');
  if (!indicator) {
    return;
  }
  const savedLabel = button.getAttribute('data-pmf-label-saved') ?? '';
  indicator.textContent = `${savedLabel} ${new Date().toLocaleTimeString()}`;
};

export const saveFaq = async (): Promise<void> => {
  const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement | null;
  const form = document.getElementById('faqEditor') as HTMLFormElement | null;

  if (!submitButton || submitButton.disabled || !form) {
    return;
  }

  const validationErrors = validateFaqEditor();
  applyValidationFeedback(validationErrors);
  if (validationErrors.length > 0) {
    showFirstValidationError(validationErrors);
    return;
  }

  const serializedData = serialize(new FormData(form)) as SerializedData;

  setSaveButtonLoading(submitButton, true);
  try {
    let response: ApiResponse | undefined;
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

      markClean();
      showSavedIndicator(submitButton);
      pushNotification(response.success);
    } else {
      if (response && response.error) {
        pushErrorNotification(response.error);
      }
    }
  } catch (error) {
    console.error(error);
    const saveErrorMessage = submitButton.getAttribute('data-pmf-msg-save-error') ?? '';
    pushErrorNotification(error instanceof Error && error.message !== '' ? error.message : saveErrorMessage);
  } finally {
    setSaveButtonLoading(submitButton, false);
  }
};

export const handleSaveFaqData = (): void => {
  const submitButton = document.getElementById('faqEditorSubmit') as HTMLButtonElement | null;

  if (submitButton) {
    submitButton.addEventListener('click', (event: Event) => {
      event.preventDefault();
      void saveFaq();
    });
  }
};

export const handleSaveShortcut = (): void => {
  document.addEventListener('keydown', (event: KeyboardEvent): void => {
    if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 's') {
      return;
    }

    const form = document.getElementById('faqEditor') as HTMLFormElement | null;
    if (!form) {
      return;
    }

    event.preventDefault();
    void saveFaq();
  });
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
    const deleteErrorMessage = deleteButton.getAttribute('data-pmf-msg-delete-error') ?? 'Could not delete the FAQ.';

    if (!faqId || !faqLanguage || !csrfToken) {
      pushErrorNotification(deleteErrorMessage);
      return;
    }

    try {
      const response = await deleteFaq(faqId, faqLanguage, csrfToken);

      if (response?.success) {
        markClean();
        pushNotification(response.success);
        window.setTimeout(() => {
          window.location.href = './faqs';
        }, 1000);
      } else if (response?.error) {
        pushErrorNotification(response.error);
      } else {
        pushErrorNotification(deleteErrorMessage);
      }
    } catch (error) {
      console.error(error);
      pushErrorNotification(deleteErrorMessage);
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

      // Reset Markdown editor if it exists
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

      markClean();
    });
  }
};

/**
 * Debounce function for performance
 */
const debounce = <T extends (...args: string[]) => void>(func: T, wait: number): ((...args: Parameters<T>) => void) => {
  let timeout: ReturnType<typeof setTimeout>;
  return (...args: Parameters<T>): void => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

/**
 * Handles real-time Flesch Reading Ease calculation
 */
export const handleFleschReadingEase = (): void => {
  const fleschScoreElement = document.getElementById('pmf-flesch-score') as HTMLElement | null;
  const fleschLabelElement = document.getElementById('pmf-flesch-label') as HTMLElement | null;
  const fleschBadgeElement = document.getElementById('pmf-flesch-badge') as HTMLElement | null;

  if (!fleschScoreElement || !fleschLabelElement || !fleschBadgeElement) {
    return;
  }

  /**
   * Gets the current FAQ language from the form
   * Maps language codes to supported Flesch formula languages
   */
  const getLanguage = (): SupportedLanguage => {
    const langSelect = document.getElementById('lang') as HTMLSelectElement | HTMLInputElement | null;
    if (!langSelect) {
      return 'en';
    }

    const lang = langSelect.value.toLowerCase().split(/[-_]/)[0];

    const languageMap: Record<string, SupportedLanguage> = {
      de: 'de',
      en: 'en',
      es: 'es',
      fr: 'fr',
      it: 'it',
      nl: 'nl',
      pt: 'pt',
      pl: 'pl',
      ru: 'ru',
      cs: 'cs',
      tr: 'tr',
      sv: 'sv',
      da: 'da',
      no: 'no',
      nb: 'no', // Norwegian Bokmål
      nn: 'no', // Norwegian Nynorsk
      fi: 'fi',
    };

    return languageMap[lang] || 'en';
  };

  /**
   * Updates the Flesch score display
   */
  const updateFleschDisplay = (content: string): void => {
    const language = getLanguage();
    const result = analyzeReadability(content, language);

    fleschScoreElement.textContent = result.score.toString();
    fleschLabelElement.textContent = result.label;

    // Update badge color
    fleschBadgeElement.className = `badge bg-${result.colorClass}`;
  };

  const debouncedUpdate = debounce(updateFleschDisplay, 300);

  // Initial calculation
  const initialContent = getAnswerContent();
  if (initialContent) {
    updateFleschDisplay(initialContent);
  }

  // Handle Jodit WYSIWYG editor
  const joditEditor = getJoditEditor();
  if (joditEditor) {
    joditEditor.events.on('change', (): void => {
      debouncedUpdate(joditEditor.value);
    });
  }

  // Handle Markdown editor
  const markdownEditor = document.getElementById('answer-markdown') as HTMLTextAreaElement | null;
  if (markdownEditor) {
    markdownEditor.addEventListener('input', (): void => {
      debouncedUpdate(markdownEditor.value);
    });
  }

  // Handle plain text editor (fallback when no Jodit)
  const plainEditor = document.getElementById('editor') as HTMLTextAreaElement | null;
  if (plainEditor && !joditEditor) {
    plainEditor.addEventListener('input', (): void => {
      debouncedUpdate(plainEditor.value);
    });
  }

  // Re-calculate when language changes
  const langSelect = document.getElementById('lang') as HTMLSelectElement | null;
  if (langSelect) {
    langSelect.addEventListener('change', (): void => {
      const content = getAnswerContent();
      updateFleschDisplay(content);
    });
  }
};
