/**
 * JavaScript functions for all FAQ category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-06-02
 */

import Sortable from 'sortablejs';

export const handleCategories = () => {
  console.log('handleCategories');

  const listGroupItems = document.querySelectorAll('.list-group-item');

  listGroupItems.forEach((element) => {
    element.addEventListener('click', (event) => {
      const hasSubCategories = event.target.querySelector('.pmf-has-subcategories');
      if (hasSubCategories) {
        hasSubCategories.classList.toggle('fa-caret-right');
        hasSubCategories.classList.toggle('fa-caret-down');
      }
    });
  });
};
