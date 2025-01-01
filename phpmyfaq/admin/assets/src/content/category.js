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

import Sortable from 'sortablejs';
import { deleteCategory, setCategoryTree } from '../api';
import { pushErrorNotification, pushNotification } from '../utils';

const nestedQuery = '.nested-sortable';
const identifier = 'pmfCatid';

export const handleCategories = () => {
  const root = document.getElementById('pmf-category-tree');
  const nestedSortables = document.querySelectorAll(nestedQuery);
  for (let i = 0; i < nestedSortables.length; i++) {
    new Sortable(nestedSortables[i], {
      group: 'Categories',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      dataIdAttr: identifier,
      onEnd: async (event) => {
        const categoryId = event.item.getAttribute('data-pmf-catid');
        const csrf = document.querySelector('input[name=pmf-csrf-token]').value;
        const data = serializedTree(root);
        const response = await setCategoryTree(data, categoryId, csrf);
        if (response.success) {
          pushNotification(response.success);
        } else {
          pushErrorNotification(response.error);
        }
      },
    });
  }

  const serializedTree = (sortable) => {
    return Array.from(sortable.children).map((child) => {
      const nested = child.querySelector(nestedQuery);
      return {
        id: child.dataset[identifier],
        children: nested ? serializedTree(nested) : [],
      };
    });
  };
};

export const handleCategoryDelete = async () => {
  const buttonDelete = document.getElementsByName('pmf-category-delete-button');

  if (buttonDelete) {
    buttonDelete.forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();
        const categoryId = event.target.getAttribute('data-pmf-category-id');
        const language = event.target.getAttribute('data-pmf-language');
        const csrfToken = document.querySelector('input[name=pmf-csrf-token]').value;

        const response = await deleteCategory(categoryId, language, csrfToken);
        if (response.success) {
          pushNotification(response.success);
        }
        document.getElementById(`pmf-category-${categoryId}`).remove();
      });
    });
  }
};
