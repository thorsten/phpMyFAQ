/**
 * JavaScript functions for all FAQ category administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2014-06-02
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  () => {
    $('.list-group-item').on('click', function() {
      $('.fa.pmf-has-subcategories', this)
        .toggleClass('fa-caret-right')
        .toggleClass('fa-caret-down');
    });
  };
});
