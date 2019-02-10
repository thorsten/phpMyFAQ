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
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-05-02
 */

/*global $:false, Bloodhound: false, Handlebars: false */

/**
 * Fetches the user rights as JSON object and checks the checkboxes
 *
 * @param userId User ID
 */
function getUserRights(userId) {
    'use strict';

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
    'use strict';

    getUserData(userId);
    getUserRights(userId);
}


$(document).ready(function() {
    'use strict';

    var button   = $('#checkAll');
    var buttonOverridePassword = $('.pmf-user-password-override-action');

    button.data('type', 'check');
    button.on('click', function (event) {
        var checkbox = $('.permission');
        event.preventDefault();
        if (button.data('type') === 'check') {
            checkbox.prop('checked', true);
            button.data('type', 'uncheck');
        } else {
            checkbox.prop('checked', false);
            button.data('type', 'check');
        }
    });

    buttonOverridePassword.on('click', function(event) {
        event.preventDefault();

        // Fetch data
        $.ajax({
            url:      'index.php?action=ajax&ajax=user&ajaxaction=overwrite_password',
            type:     'POST',
            data:     $('#pmf-modal-user-password-override form').serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#saving_data_indicator').html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Saving ...');
            },
            success: function(message) {
                $('.pmf-admin-override-password').replaceWith('<p>✓ ' + message.success + '</p>');
                $('#pmf-modal-user-password-override').modal('hide');
                $('#saving_data_indicator').fadeOut();
            }
        });
        return false;
    });

    // Instantiate the bloodhound suggestion engine
    var users = new Bloodhound({
        datumTokenizer: function (d) {
            return Bloodhound.tokenizers.whitespace(d.value);
        },
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: 'index.php?action=ajax&ajax=user&ajaxaction=get_user_list&q=%QUERY',
            wildcard: '%QUERY',
            filter: function (users) {
                return $.map(users.results, function (users) {
                    return {
                        userId: users.user_id,
                        userName: users.name
                    };
                });
            }
        }
    });

    // Initialize the bBloodhound suggestion engine
    users.initialize();

    // Instantiate the Typeahead UI
    $('.pmf-user-autocomplete').typeahead(null, {
        source: users.ttAdapter(),
        displayKey: 'users',
        name: 'users',
        minLength: 1,
        templates: {
            empty: [
                '<div class="empty-message">',
                'unable to find any Best Picture winners that match the current query',
                '</div>'
            ].join('\n'),
            suggestion: Handlebars.compile('<div data-userId="{{userId}}">{{userName}}</div>')
        }
    }).on('typeahead:selected typeahead:autocompleted', function (event, user) {
        $('.pmf-user-autocomplete').typeahead('val', user.userName);
        $('#user_list_select').val(user.userId);
        updateUser(user.userId);
    });
});