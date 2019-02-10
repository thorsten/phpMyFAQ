/**
 * JavaScript functions for all tag administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-16
 */

/*global $:false */

$(document).ready(function () {
    'use strict';

    $('.btn-edit').on('click', function () {
        var id = $(this).data('btn-id');
        var span = $('span[data-tag-id="' + id + '"]');

        if (span.length > 0) {
            span.replaceWith(
                '<input name="tag" class="form-control" data-tag-id="' + id + '" value="' + span.html() + '">'
            );
        } else {
            var input = $('input[data-tag-id="' + id + '"]');
            input.replaceWith('<span data-tag-id="' + id + '">' + input.val().replace(/\//g, '&#x2F;') + '</span>');
        }
    });

    $('.tag-form').bind('submit', function (event) {

        event.preventDefault();

        var input = $('input[data-tag-id]:focus');
        var id = input.data('tag-id');
        var tag = input.val();
        var csrf = $('input[name=csrf]').val();

        $.ajax({
            url: 'index.php?action=ajax&ajax=tags&ajaxaction=update',
            type: 'POST',
            data: 'id=' + id + '&tag=' + tag + '&csrf=' + csrf,
            dataType: 'json',
            beforeSend: function () {
                $('#saving_data_indicator').html(
                    '<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Saving ...'
                );
            },
            success: function (message) {
                input.replaceWith('<span data-tag-id="' + id + '">' + input.val().replace(/\//g, '&#x2F;') + '</span>');
                $('span[data-tag-id="' + id + '"]');
                $('#saving_data_indicator').html(message);
            }
        });

        return false;
    });
});
