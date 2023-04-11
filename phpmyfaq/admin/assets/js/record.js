/**
 * JavaScript functions for all FAQ record administration stuff
 *
 * @deprecated needs to be rewritten without jQuery
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-11-17
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Typeahead
  $('.pmf-tags-autocomplete').typeahead({
    autoSelect: true,
    delay: 300,
    fitToElement: true,
    minLength: 1,
    showHintOnFocus: 'all',
    source: (request, response) => {
      const tags = $('#tags');
      let currentTags = tags.data('tag-list');

      if (currentTags.length > 0) {
        request = request.substr(currentTags.length + 1, request.length);
      }
      $.ajax({
        url: 'index.php?action=ajax&ajax=tags&ajaxaction=list',
        type: 'GET',
        dataType: 'JSON',
        data: 'q=' + request.trim(),
        success: (data) => {
          response(
            data.map((tags) => {
              return {
                tagName: tags.tagName,
              };
            })
          );
        },
      });
    },
    displayText: (tags) => {
      return typeof tags !== 'undefined' && typeof tags.tagName !== 'undefined' ? tags.tagName : tags;
    },
    updater: (event) => {
      const tags = $('#tags');
      let currentTags = tags.data('tag-list');
      if (typeof currentTags === 'undefined') {
        currentTags = event.tagName;
      } else {
        currentTags = currentTags + ', ' + event.tagName;
      }
      tags.data('tagList', currentTags);
      tags.val(currentTags);
      return event;
    },
  });
});
