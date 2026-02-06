/**
 * Category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-06-02
 */

import Sortable, { SortableEvent } from 'sortablejs';
import { Modal } from 'bootstrap';
import { deleteCategory, setCategoryTree } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';
import { Translator } from '../translation/translator';

const nestedQuery = '.nested-sortable';
const identifier = 'pmfCatid';

interface SerializedTree {
  id: string;
  children: SerializedTree[];
}

export const handleCategories = (): void => {
  const root = document.getElementById('pmf-category-tree') as HTMLElement;
  const nestedSortables: NodeListOf<HTMLElement> = document.querySelectorAll<HTMLElement>(nestedQuery);
  for (let i: number = 0; i < nestedSortables.length; i++) {
    new Sortable(nestedSortables[i], {
      group: 'Categories',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      dataIdAttr: identifier,
      emptyInsertThreshold: 10,
      onStart: (): void => {
        // Add class to all empty drop zones when drag starts
        const emptySortables = document.querySelectorAll<HTMLElement>(`${nestedQuery}:empty`);
        emptySortables.forEach((sortable: HTMLElement): void => {
          sortable.classList.add('sortable-drag-active');
        });
      },
      onEnd: async (event: SortableEvent): Promise<void> => {
        // Remove the class from all drop zones when drag ends
        const allSortables = document.querySelectorAll<HTMLElement>(nestedQuery);
        allSortables.forEach((sortable: HTMLElement): void => {
          sortable.classList.remove('sortable-drag-active');
        });

        const categoryId = event.item.getAttribute('data-pmf-catid') as string;
        const csrf: string = (document.querySelector('input[name=pmf-csrf-token]') as HTMLInputElement).value;
        const data: SerializedTree[] = serializedTree(root);
        const response = (await setCategoryTree(data, categoryId, csrf)) as unknown as Response;
        if (response.success) {
          pushNotification(response.success);
        } else {
          pushErrorNotification(response.error as string);
        }
      },
    });
  }

  const serializedTree = (sortable: HTMLElement): SerializedTree[] => {
    return Array.from(sortable.children).map((child: Element): SerializedTree => {
      const nested = child.querySelector(nestedQuery) as HTMLElement;
      return {
        id: (child as HTMLElement).dataset[identifier] as string,
        children: nested ? serializedTree(nested) : [],
      };
    });
  };
};

export const handleCategoryDelete = async (): Promise<void> => {
  const deleteButtons: NodeListOf<HTMLElement> = document.getElementsByName('pmf-category-delete-button');
  const modalElement = document.getElementById('deleteConfirmModal') as HTMLElement;

  if (!modalElement || !deleteButtons) {
    return;
  }

  const deleteModal = new Modal(modalElement);
  const confirmButton = document.getElementById('confirmDeleteButton') as HTMLButtonElement;

  let currentCategoryId: string = '';
  let currentLanguage: string = '';

  deleteButtons.forEach((button: HTMLElement): void => {
    button.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const target = event.target as HTMLElement;
      currentCategoryId = target.getAttribute('data-pmf-category-id') as string;
      currentLanguage = target.getAttribute('data-pmf-language') as string;
      deleteModal.show();
    });
  });

  confirmButton.addEventListener('click', async (): Promise<void> => {
    if (!currentCategoryId || !currentLanguage) {
      return;
    }

    const csrfToken: string = (document.querySelector('input[name=pmf-csrf-token]') as HTMLInputElement).value;
    const response = (await deleteCategory(currentCategoryId, currentLanguage, csrfToken)) as unknown as Response;
    if (response.success) {
      pushNotification(response.success);
    }

    document.getElementById(`pmf-category-${currentCategoryId}`)?.remove();
    deleteModal.hide();

    currentCategoryId = '';
    currentLanguage = '';
  });
};

export const handleResetCategoryImage = (): void => {
  const resetButton = document.getElementById('button-reset-category-image') as HTMLButtonElement;

  if (resetButton) {
    const categoryExistingImage = document.getElementById('pmf-category-existing-image') as HTMLInputElement;
    const categoryImageInput = document.getElementById('pmf-category-image-upload') as HTMLInputElement;
    const categoryImageLabel = document.getElementById('pmf-category-image-label') as HTMLLabelElement;
    resetButton.addEventListener('click', (): void => {
      categoryImageInput.value = '';
      categoryExistingImage.value = '';
      categoryImageLabel.innerHTML = '';
    });
  }
};

export const handleCategoryTranslate = (): void => {
  const translateButton = document.getElementById('btn-translate-category-ai') as HTMLButtonElement | null;
  const langSelect = document.getElementById('catlang') as HTMLSelectElement | null;
  const originalLangInput = document.getElementById('originalCategoryLang') as HTMLInputElement | null;

  if (!translateButton || !langSelect || !originalLangInput) {
    return;
  }

  // Initialize translator when the target language is selected
  langSelect.addEventListener('change', () => {
    const sourceLang = originalLangInput.value;
    const targetLang = langSelect.value;

    if (sourceLang && targetLang && sourceLang !== targetLang) {
      // Enable the translation button
      translateButton.disabled = false;

      // Initialize the Translator
      try {
        new Translator({
          buttonSelector: '#btn-translate-category-ai',
          contentType: 'category',
          sourceLang: sourceLang,
          targetLang: targetLang,
          fieldMapping: {
            name: '#name',
            description: '#description',
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
      // Disable the translation button if same language or no target language
      translateButton.disabled = true;
    }
  });

  // Initially disable the button
  translateButton.disabled = true;
};
