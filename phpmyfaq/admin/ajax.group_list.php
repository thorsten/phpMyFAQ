<?php
/**
* $Id: ajax.group_list.php,v 1.10 2006-09-19 21:28:33 matteo Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
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
$user->addDb($db);
$userList = $user->getAllUsers();
$groupList = is_a($user->perm, "PMF_PermMedium") ? $user->perm->getAllGroups() : array();
$data = array(
    'name' => "Name:",
    'description' => "Description:",
    'auto_join' => "Auto-join:",
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
    // right is not for groups!
    if (!$right_data['for_groups'])
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
?>
        <user id="<?php print $user_id; ?>">
            <login><?php print $user_object->getLogin(); ?></login>
        </user>
<?php
} /* end foreach ($userList) */
?>
    </userlist>
    <grouplist>
        <select_class>ad_select_group</select_class>
<?php
foreach ($groupList as $group_id) {
    $groupData = $perm->getGroupData($group_id);
?>
        <group id="<?php print $groupData['group_id']; ?>">
            <name><?php print $groupData['name']; ?></name>
            <description><?php print $groupData['description']; ?></description>
            <auto_join><?php print $groupData['auto_join']; ?></auto_join>
            <group_rights>
<?php
    foreach ($all_rights as $right_data) {
        $right_id = $right_data['right_id'];
        // right is not for groups!
        if (!$right_data['for_groups'])
            continue;
        // right is a group right!
        if ($perm->checkGroupRight($group_id, $right_id)) {
?>
                <right id="<?php print $right_id; ?>"></right>
<?php
        } /* end if ($perm->checkGroupRight()) */
    } /* end foreach ($all_rights) */
?>
            </group_rights>
            <group_members>
<?php
    foreach ($perm->getGroupMembers($group_id) as $member_id) {
        $member = new PMF_User();
        $member->getUserById($member_id);
?>
                <user id="<?php print $member->getUserId(); ?>"></user>
<?php
    } /* end $perm->getGroupMembers($group_id) as $member_id) */
?>
            </group_members>
        </group>
<?php
} /* end foreach ($groupList) */
?>
    </grouplist>
</xml>
