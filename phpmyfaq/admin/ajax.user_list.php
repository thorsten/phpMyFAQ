<?php
/**
* $Id: ajax.user_list.php,v 1.21 2006-09-19 21:39:38 matteo Exp $
*
* AJAX: lists all registered users
*
* @author       Lars Tiedemann <larstiedemann@yahoo.de>
* @since        2005-12-15
* @copyright    (c) 2005-2006 phpMyFAQ Team
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

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type: text/xml");
    header("Vary: Negotiate,Accept");

    require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');

    $user = new PMF_User();
    $user->addDb($db);
    $userList = $user->getAllUsers();
    $data = array(
        'display_name'  => $PMF_LANG["ad_user_realname"], //"real name:",
        'email'         => $PMF_LANG["ad_entry_email"], //"email adress:"
        'last_modified' => $PMF_LANG['ad_user_lastModified'], //"last modified:",
    );
    $perm = $user->perm;
    $all_rights = $perm->getAllRightsData();

    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
?>
<xml>
    <rightlist>
<?php
    foreach ($all_rights as $right_data) {
        $right_id = $right_data['right_id'];
        // right is not for users!
        if (!$right_data['for_users'])
            continue;
?>
        <right id="<?php print $right_id; ?>">
            <name><?php print isset($PMF_LANG['rightsLanguage'][$right_data['name']]) ? PMF_htmlentities($PMF_LANG['rightsLanguage'][$right_data['name']]) : $right_data['name']; ?></name>
            <description><?php print $right_data['description']; ?></description>
        </right>
<?php
        } /* end foreach ($all_rights) */
?>
    </rightlist>
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
        foreach ($all_rights as $right_data) {
            $right_id = $right_data['right_id'];
            // right is not for users!
            if (!$right_data['for_users'])
                continue;
            // right is a user right!
            if ($perm->checkUserRight($user_id, $right_id)) {
?>
                <right id="<?php print $right_id; ?>"></right>
<?php
            } /* end if ($perm->checkUserRight()) */
        } /* end foreach ($all_rights) */
?>
            </user_rights>
        </user>
<?php
    } /* end foreach ($userList) */
?>
    </userlist>
</xml>
<?php
}
