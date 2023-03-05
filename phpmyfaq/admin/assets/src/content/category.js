/**
 * Category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-06-02
 */

import Sortable from 'sortablejs';
import { addElement } from '../../../../assets/src/utils';

export const handleCategories = () => {
  const listGroupItems = document.querySelectorAll('.list-group-item');
  const sortableCategories = document.querySelector('.list-group.list-group-root');

  listGroupItems.forEach((element) => {
    element.addEventListener('click', (event) => {
      const hasSubCategories = event.target.querySelector('.pmf-has-subcategories');
      if (hasSubCategories) {
        hasSubCategories.classList.toggle('fa-caret-right');
        hasSubCategories.classList.toggle('fa-caret-down');
      }
    });
  });

  if (sortableCategories) {
    Sortable.create(sortableCategories, {
      animation: 150,
      dataIdAttr: 'data-id',
      filter: '.pmf-category-not-sortable',
      group: 'phpmyfaq.category.order',
      store: {
        /**
         * Get the order of elements. Called once during initialization.
         * @param   {Sortable}  sortable
         * @returns {Array}
         */
        get: (sortable) => {
          const order = localStorage.getItem(sortable.options.group.name);
          return order ? order.split('|') : [];
        },

        /**
         * Save the order of elements. Called onEnd (when the item is dropped).
         * @param {Sortable}  sortable
         */
        set: (sortable) => {
          const order = sortable.toArray();
          const csrf = document.querySelector('input[name=pmf-csrf-token]').value;
          localStorage.setItem(sortable.options.group.name, order.join('|'));

          fetch('index.php?action=ajax&ajax=categories&ajaxaction=update-order', {
            method: 'POST',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: csrf,
              order: order,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
                return response.json();
              }
              throw new Error('Network response was not ok.');
            })
            .then((response) => {
              sortableCategories.insertAdjacentElement(
                'beforebegin',
                addElement('div', { classList: 'alert alert-success', innerText: response.success })
              );
            })
            .catch((error) => {
              sortableCategories.insertAdjacentElement(
                'beforebegin',
                addElement('div', { classList: 'alert alert-danger', innerText: error })
              );
            });
        },
      },
    });
  }
};
