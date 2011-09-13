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
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-04
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$userId     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$usersearch = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {

    $user = new PMF_User();
    
    switch ($ajaxAction) {

        case 'get_user_list':
            foreach ($user->searchUsers($usersearch) as $singleUser) {
                print $singleUser['login'] . '|' .  $singleUser['user_id'] . "\n";
            }
            break;

        case 'get_user_data':
            $user->getUserById($userId);
            $userdata           = array();
            $userdata           = $user->userdata->get('*');
            $userdata['status'] = $user->getStatus();
            $userdata['login']  = $user->getLogin();
            print json_encode($userdata);
            break;

        case 'get_user_rights':
            $user->getUserById($userId);
            print json_encode($user->perm->getUserRights($userId));
            break;

        case 'delete_user':
            $user->getUserById($userId);
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message = '<p class="error">' . $PMF_LANG['ad_user_error_protectedAccount'] . '</p>';
            } else {
                if (!$user->deleteUser()) {
                    $message = $PMF_LANG['ad_user_error_delete'];
                } else {
                    $category = new PMF_Category();
                    $category->moveOwnership($userId, 1);

                    // Remove the user from groups
                    if ('medium' == PMF_Configuration::getInstance()->get('security.permLevel')) {
                        $permissions = PMF_Perm::selectPerm('medium');
                        $permissions->removeFromAllGroups($userId);
                    }
    
                    $message = '<p class="success">' . $PMF_LANG['ad_user_deleted'] . '</p>';
                }
            }
            print json_encode($message);
            break;

    }
    
}