<?php
/**
 * AJAX: Lists the user auto completion
 *
 * @package   phpMyFAQ
 * @author    Sarah Hermann <sayh@gmx.de>
 * @copyright 2008 phpMyFAQ Team
 * @version   CVS: $Id: ajax.user_list_autocomplete.php,v 1.1 2008-01-26 15:43:36 thorstenr Exp $
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

require_once PMF_ROOT_DIR.'/inc/PMF_User/User.php';

$user = new PMF_User();
$userList = $user->searchUsers($_REQUEST['user_list_search']);

echo "<ul id='user_list_autocomplete' class='user_list_autocomplete'>";
foreach ($userList as $user) {
    echo "<li id='".$user['user_id']."'>".$user['login']."</li>";
}
echo "</ul>";