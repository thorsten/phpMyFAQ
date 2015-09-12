/**
 * JavaScript functions for user frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-05-02
 */

/*global $:false */

/**
 * Fetches the user rights as JSON object and checks the checkboxes
 *
 * @param userId User ID
 */
function getUserRights(userId) {
    $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=get_user_rights&user_id=' + userId,
        function(data) {
            $.each(data, function(i, val) {
                $('#user_right_' + val).attr('checked', true);
            });
            $('#rights_user_id').val(userId);
        });
}

/**
 * Updates the user data in forms
 *
 * @param userId User ID
 */
function updateUser(userId) {
    getUserData(userId);
    getUserRights(userId);
}


$(document).ready(function() {
    "use strict";

    var button   = $('#checkAll');
    var checkbox = $('.permission');

    button.data('type', 'check');
    button.click(function(event) {
        event.preventDefault();
        if (button.data('type') === 'check') {
            checkbox.prop('checked', true);
            button.data('type', 'uncheck');
        } else {
            checkbox.prop('checked', false);
            button.data('type', 'check');
        }
    });

    var buttonOverridePassword = $('.pmf-user-password-override-action');

    buttonOverridePassword.click(function(event) {
        event.preventDefault();

        // Check if passwords are equal

        // Fetch data
        $.ajax({
            url:      'index.php?action=ajax&ajax=user&ajaxaction=overwrite_password',
            type:     'POST',
            data:     $('#pmf-modal-user-password-override form').serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#saving_data_indicator').html('<i class="fa fa-spinner fa-spin"></i> Saving ...');
            },
            success: function(message) {
                $('.pmf-admin-override-password').replaceWith('<p>✓ ' + message.success + '</p>');
                $('#pmf-modal-user-password-override').modal('hide');
                $('#saving_data_indicator').fadeOut();
            }
        });
        return false;
    });
});