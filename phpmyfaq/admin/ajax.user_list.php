<?php
/**
* $Id: ajax.user_list.php,v 1.8 2005-12-26 20:14:21 b33blebr0x Exp $
*
* AJAX: lists all registered users
*
* @author       Lars Tiedemann <larstiedemann@yahoo.de>
* @since        2005-12-15
* @copyright    (c) 2005 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
@header("Cache-Control: no-store, no-cache, must-revalidate");
@header("Cache-Control: post-check=0, pre-check=0", false);
@header("Pragma: no-cache");
@header("Content-type: text/xml");
@header("Vary: Negotiate,Accept");

require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');

$user = new PMF_User();
$userList = $user->getAllUsers();
$data = array(
    'display_name' => 'Name: ',
    'email' => 'Email: ',
    'last_modified' => 'Last modified: '
);

ob_clean();
?>
<xml>
    <userlist>
        <select_class>ad_select_user</select_class>
<?php
foreach ($userList as $user_id) {
    $user_object = new PMF_User();
    $user_object->getUserById($user_id);
    $user_id = $user_object->getUserId();
    $login = $user_object->getLogin();
    $class = "ad_select_user";
    $status = $user_object->getStatus();
?>
        <user id="<?php print $user_id; ?>">
            <login><?php print $login; ?></login>
            <status><?php print $status; ?></status>
            <user_data>
<?php
    $user_data = $user_object->userdata->get(array_keys($data));
    foreach ($user_data as $field => $value) {
?>

                <item name="<?php print $field; ?>">
                    <name><?php print $data[$field]; ?></name>
                    <value><?php print $value; ?></value>
                </item>
<?php
    } /* end foreach ($user_data) */
?>
            </user_data>
            <user_rights>
<?php
    $perm = $user_object->perm;
    $all_rights = $perm->getAllRights();
    foreach ($all_rights as $right_id) {
        $right_data = $perm->getRightData($right_id);
        // right is not for users!
        if (!$right_data['for_users'])
            continue;
        $isUserRight = $perm->checkUserRight($user_id, $right_id) ? '1' : '0';
?>
                <right id="<?php print $right_id; ?>">
                    <name><?php print $right_data['name']; ?></name>
                    <description><?php print $right_data['description']; ?></description>
                    <is_user_right><?php print $isUserRight; ?></is_user_right>
                </right>
<?php
    } /* end foreach ($all_rights) */
?>
            </user_rights>
        </user>
<?php
} /* end foreach ($userList) */
?>
    </userlist>
</xml>
