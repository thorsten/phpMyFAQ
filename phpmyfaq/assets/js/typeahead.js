/**
 * Typeahead functionality JavaScript part
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-11-23
 */

/*global $: false */
$(window).on('load', () => {
  'use strict';
  $('.typeahead').typeahead({
    autoSelect: true,
    delay: 300,
    minLength: 1,
    source: (request, response) => {
      $.ajax({
        url: 'ajaxresponse.php',
        type: 'GET',
        dataType: 'JSON',
        data: 'search=' + request,
        success: (data) => {
          response(data.map((item) => {
            return {
              url: item.faqLink,
              question: item.faqQuestion
            };
          }));
        }
      });
    },
    displayText: (item) => {
      return typeof item !== 'undefined' && typeof item.question !== 'undefined' ? item.question : item;
    },
    afterSelect: (event) => {
      window.location.href = event.url;
    }
  });

});
