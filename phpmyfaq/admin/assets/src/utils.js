/**
 * Utility functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-04
 */

export const selectAllCheckboxes = (selectId) => {
  for (const options of [...document.querySelector(`#${selectId}`).options]) {
    options.selected = true;
  }
};

export const unSelectAllCheckboxes = (selectId) => {
  for (const options of [...document.querySelector(`#${selectId}`).options]) {
    options.selected = false;
  }
};
