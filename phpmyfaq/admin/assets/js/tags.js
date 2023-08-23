/**
 * JavaScript functions for all tag administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2014-08-16
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  $('.btn-edit').on('click', function () {
    const id = $(this).data('btn-id');
    const span = $('span[data-tag-id="' + id + '"]');

    if (span.length > 0) {
      span.replaceWith('<input name="tag" class="form-control" data-tag-id="' + id + '" value="' + span.html() + '">');
    } else {
      const input = $('input[data-tag-id="' + id + '"]');
      input.replaceWith('<span data-tag-id="' + id + '">' + escape(input.val().replace(/\//g, '&#x2F;')) + '</span>');
    }
  });

  $('.tag-form').bind('submit', function (event) {
    event.preventDefault();

    const input = $('input[data-tag-id]:focus');
    const id = input.data('tag-id');
    const tag = input.val();
    const csrf = $('input[name=csrf]').val();

    $.ajax({
      url: 'index.php?action=ajax&ajax=tags&ajaxaction=update',
      type: 'POST',
      data: 'id=' + id + '&tag=' + tag + '&csrf=' + csrf,
      dataType: 'json',
      beforeSend: function () {
        $('#pmf-admin-saving-data-indicator').html(
          '<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Saving ...</span>'
        );
      },
      success: function (message) {
        input.replaceWith('<span data-tag-id="' + id + '">' + escape(input.val().replace(/\//g, '&#x2F;')) + '</span>');
        $('span[data-tag-id="' + id + '"]');
        $('#pmf-admin-saving-data-indicator').html(message);
      },
    });

    return false;
  });

  const escape = (unsafe) => {
    return unsafe.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  }
});
