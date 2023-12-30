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

export const handleCategories = () => {
  const nestedSortables = document.querySelectorAll('.nested-sortable');
  for (let i = 0; i < nestedSortables.length; i++) {
    new Sortable(nestedSortables[i], {
      group: 'nested',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      dataIdAttr: 'data-pmf-catid',
      store: {
        get: (sortable) => {
          const order = localStorage.getItem(sortable.options.group.name);
          return order ? order.split('|') : [];
        },
        set: (sortable) => {
          const order = sortable.toArray();
          //const csrf = document.querySelector("input[name=pmf-csrf-token]").value;
          localStorage.setItem(sortable.options.group.name, order.join('|'));
        },
      },
    });
  }
};
