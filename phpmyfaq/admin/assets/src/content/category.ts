/**
 * Category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-06-02
 */

import Sortable, { SortableEvent } from 'sortablejs';
import { deleteCategory, setCategoryTree } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

const nestedQuery = '.nested-sortable';
const identifier = 'pmfCatid';

interface SerializedTree {
  id: string;
  children: SerializedTree[];
}

export const handleCategories = (): void => {
  const root = document.getElementById('pmf-category-tree') as HTMLElement;
  const nestedSortables = document.querySelectorAll<HTMLElement>(nestedQuery);
  for (let i = 0; i < nestedSortables.length; i++) {
    new Sortable(nestedSortables[i], {
      group: 'Categories',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      dataIdAttr: identifier,
      onEnd: async (event: SortableEvent) => {
        const categoryId = event.item.getAttribute('data-pmf-catid') as string;
        const csrf = (document.querySelector('input[name=pmf-csrf-token]') as HTMLInputElement).value;
        const data = serializedTree(root);
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
    return Array.from(sortable.children).map((child) => {
      const nested = child.querySelector(nestedQuery) as HTMLElement;
      return {
        id: child.dataset[identifier] as string,
        children: nested ? serializedTree(nested) : [],
      };
    });
  };
};

export const handleCategoryDelete = async (): Promise<void> => {
  const buttonDelete = document.getElementsByName('pmf-category-delete-button');

  if (buttonDelete) {
    buttonDelete.forEach((button) => {
      button.addEventListener('click', async (event: Event) => {
        event.preventDefault();
        const target = event.target as HTMLElement;
        const categoryId = target.getAttribute('data-pmf-category-id') as string;
        const language = target.getAttribute('data-pmf-language') as string;
        const csrfToken = (document.querySelector('input[name=pmf-csrf-token]') as HTMLInputElement).value;

        const response = (await deleteCategory(categoryId, language, csrfToken)) as unknown as Response;
        if (response.success) {
          pushNotification(response.success);
        }
        document.getElementById(`pmf-category-${categoryId}`)?.remove();
      });
    });
  }
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
