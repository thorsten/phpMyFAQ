<?php
/**
 * AJAX: Lists the user auto completion
 *
 * @todo Switch code and logic to jQuery and PHP JSON extension
 * 
 * @package    phpMyFAQ
 * @subpackage Administration Ajax
 * @author     Sarah Hermann <sayh@gmx.de>
 * @copyright  2008-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$user = new PMF_User_User();
$userList = $user->searchUsers($_REQUEST['user_list_search']);

echo "<ul id='user_list_autocomplete' class='user_list_autocomplete'>";
foreach ($userList as $user) {

    if ($user['account_status'] == 'blocked') {
        echo "<li id='".$user['user_id']."' style='color: red;'><strong>".$user['login']."</strong></li>";
    } else {
        echo "<li id='".$user['user_id']."' >".$user['login']."</li>";
    }
}
echo "</ul>";
