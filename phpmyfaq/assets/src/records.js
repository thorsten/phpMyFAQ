/**
 * FAQ record functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Hamed Ayari <hamed.ayari@maxdome.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2016-03-25
 */

/*global $: false, saveFormValues: false */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  //
  // Show rating
  //
  if (document.querySelector('#rating span')) {
    let rating = parseInt(document.querySelector('#rating span').dataset.rating);
    if (0 < rating) {
      rating = Math.floor(rating);

      $('.pmf-star-rating')
        .children('span')
        .each(function () {
          if ($(this).data('stars') <= rating) {
            $(this).text('★');
          }
        });
    }
  }

  //
  // Save comments
  //
  $('form#formValues').on('submit', function (e) {
    e.preventDefault();
    saveFormValues('savecomment', 'comment');
    return false;
  });

  //
  // Tooltips
  //
  $('[data-toggle="tooltip"]').tooltip();
});
