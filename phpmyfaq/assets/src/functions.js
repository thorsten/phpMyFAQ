/**
 * Some JavaScript functions used in the admin backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-11-13
 */

/*global document: false, window: false, $: false */

let saveVoting;

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /**
   * Saves the voting by Ajax
   *
   * @param type
   * @param id
   * @param value
   * @param lang
   */
  saveVoting = function saveVoting(type, id, value, lang) {
    const votings = $('#votings');
    const loader = $('#loader');
    $.ajax({
      type: 'post',
      url: 'api.service.php?action=savevoting',
      data: 'type=' + type + '&id=' + id + '&vote=' + value + '&lang=' + lang,
      dataType: 'json',
      cache: false,
      success: function (json) {
        if (json.success === undefined) {
          votings.append(
            '<p class="alert alert-danger">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.error +
              '</p>'
          );
          loader.hide();
        } else {
          votings.append(
            '<p class="alert alert-success">' +
              '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
              json.success +
              '</p>'
          );
          $('#rating').empty().append(json.rating);
          votings.fadeIn('slow');
          loader.hide();
        }
      },
    });

    return false;
  };
});
