<?php
/**
 * The export function to import the phpMyFAQ backups
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
 * @since     2009-08-18
 */

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

$auth = false;
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
if ($user) {
    $auth = true;
} else {
    $user = null;
    unset($user);
}

//
// Get current user rights
//
$permission = array();
if ($auth === true) {
    // read all rights, set them FALSE
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }
    // check user rights, set them TRUE
    $allUserRights = $user->perm->getAllUserRights($user->getUserId());
    foreach ($allRights as $right) {
        if (in_array($right['right_id'], $allUserRights))
            $permission[$right['name']] = true;
    }
}

header('Content-Type: application/octet-stream');
header('Pragma: no-cache');

if ($permission['backup']) {

    $db->getTableNames ( SQLPREFIX );
    $tablenames   = '';
    $majorVersion = substr($faqconfig->get('main.currentVersion'), 0, 3);

    switch ($action) {
        case 'backup_content' :
            foreach ($db->tableNames as $table) {
                if (SQLPREFIX . 'faqadminlog' != $table || SQLPREFIX . 'faqsessions' != $table) {
                    $tablenames .= $table . ' ';
                }
            }
            break;
        case 'backup_logs' :
            foreach ($db->tableNames as $table) {
                if (SQLPREFIX . 'faqadminlog' == $table || SQLPREFIX . 'faqsessions' == $table) {
                    $tablenames .= $table . ' ';
                }
            }
            break;
    }
    
    $text[] = "-- pmf" . $majorVersion . ": " . $tablenames;
    $text[] = "-- DO NOT REMOVE THE FIRST LINE!";
    $text[] = "-- pmftableprefix: " . SQLPREFIX;
    $text[] = "-- DO NOT REMOVE THE LINES ABOVE!";
    $text[] = "-- Otherwise this backup will be broken.";

    switch ($action) {
        case 'backup_content' :
            header('Content-Disposition: attachment; filename="phpmyfaq-data.'.date("Y-m-d-H-i-s").'.sql');
            foreach ($db->tableNames as $table) {
                print implode("\r\n", $text);
                $text = build_insert("SELECT * FROM " . $table, $table);
            }
            break;
        case 'backup_logs' :
            header('Content-Disposition: attachment; filename="phpmyfaq-logs.'.date("Y-m-d-H-i-s").'.sql');
            foreach ($db->tableNames as $table) {
                if (SQLPREFIX . 'faqadminlog' == $table || SQLPREFIX . 'faqsessions' == $table) {
                    print implode("\r\n", $text);
                    $text = build_insert("SELECT * FROM " . $table, $table);
                }
            }
            break;
    }

} else {
    print $PMF_LANG['err_NotAuth'];
}