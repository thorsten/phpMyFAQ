<?php
/**
 * AJAX: handling of Ajax user calls
 * 
 * PHP Version 5.2
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
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-04
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$user_id     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$usersearch  = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {

    $user = new PMF_User();
    
    if ('get_user_list' == $ajax_action) {
        foreach ($user->searchUsers($usersearch) as $single_user) {
            print $single_user['login'] . '|' .  $single_user['user_id'] . "\n";
        }
    }
    
    $user->getUserById($user_id);
    
    // Return the user data
    if ('get_user_data' == $ajax_action) {
        $userdata           = array();
        $userdata           = $user->userdata->get('*');
        $userdata['status'] = $user->getStatus();
        $userdata['login']  = $user->getLogin();
        print json_encode($userdata);
    }
    
    // Return the user rights
    if ('get_user_rights' == $ajax_action) {
        print json_encode($user->perm->getUserRights($user_id));
    }
}