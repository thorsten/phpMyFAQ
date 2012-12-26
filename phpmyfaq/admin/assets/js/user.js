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
 * @copyright 2010-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-05-02
 */

/**
 * Fetches the user rights as JSON object and checks the checkboxes
 *
 * @param userId User ID
 */
function getUserRights(userId) {
    $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=get_user_rights&user_id=" + userId,
        function(data) {
            $.each(data, function(i, val) {
                $('#user_right_' + val).attr('checked', true);
            });
            $('#rights_userId').val(userId);
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