<?php
/**
 * AJAX: handling of Ajax group calls
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-04-06
 * @copyright  2009 phpMyFAQ Team
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

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$group_id      = PMF_Filter::filterInput(INPUT_POST, 'groupid', FILTER_VALIDATE_INT);

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {
	
	$user      = new PMF_User();
    $userList  = $user->getAllUsers();
    $groupList = ($user->perm instanceof PMF_Perm_PermMedium) ? $user->perm->getAllGroups() : array();
    
    // Returns all groups
    if ('get_all_groups') {
    	$groups = array();
    	foreach ($groupList as $group_id) {
            $data     = $user->perm->getGroupData($group_id);
            $groups[] = array('group_id' => $data['group_id'],
                              'name'     => $data['name']);
    	}
        print json_encode($groups);
    }
    
    // Return the group data
    if ('get_group_data' == $ajax_action) {
        print json_encode($user->perm->getGroupData($group_id));
    }
    
    // Return the group rights
    if ('get_group_rights' == $ajax_action) {
    	print json_encode($user->perm->getGroupRights($group_id));
    }
    
    // Return all users
    if ('get_all_users' == $ajax_action) {
        print json_encode(array_map('utf8_encode', $userList));
    }
    
    // Returns all group members
    if ('get_all_group_members' == $ajax_action) {
        
    }
}