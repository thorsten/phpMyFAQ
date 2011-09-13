/**
 * JavaScript functions for user functions
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-05-02
 */

/**
 * Fetches the user rights as JSON object and checks the checkboxes
 *
 * @param integer user_id User ID
 */
function getUserRights(user_id)
{
    form_uncheckAll('rightsForm');
    $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=get_user_rights&user_id=" + user_id,
        function(data) {
            $.each(data, function(i, val) {
                $('#user_right_' + val).attr('checked', true);
            });
            $('#rights_user_id').val(user_id);
        });
}

/**
 * Updates the user data in forms
 *
 * @return void
 */
function updateUser(user_id)
{
    getUserData(user_id);
    getUserRights(user_id);
}