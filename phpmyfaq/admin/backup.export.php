<?php
/**
 * The export function to import the phpMyFAQ backups
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-08-18
 */

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqConfig->get('main.phpMyFAQToken')));
session_start();

$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

$auth = false;
$user = PMF_User_CurrentUser::getFromSession($faqConfig);
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
    $majorVersion = substr($faqConfig->get('main.currentVersion'), 0, 3);
    $dbHelper     = new PMF_DB_Helper($faqConfig);

    switch ($action) {
        case 'backup_content' :
            foreach ($db->tableNames as $table) {
                if ((SQLPREFIX . 'faqadminlog' == trim($table)) || (SQLPREFIX . 'faqsessions' == trim($table))) {
                    continue;
                }
                $tablenames .= $table . ' ';
            }
            break;
        case 'backup_logs' :
            foreach ($db->tableNames as $table) {
                if ((SQLPREFIX . 'faqadminlog' == trim($table)) || (SQLPREFIX . 'faqsessions' == trim($table))) {
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
            foreach (explode(' ', $tablenames) as $table) {
                print implode("\r\n", $text);
                $text = $dbHelper->buildInsertQueries("SELECT * FROM " . $table, $table);
            }
            break;
        case 'backup_logs' :
            header('Content-Disposition: attachment; filename="phpmyfaq-logs.'.date("Y-m-d-H-i-s").'.sql');
            foreach (explode(' ', $tablenames) as $table) {
                print implode("\r\n", $text);
                $text = $dbHelper->buildInsertQueries("SELECT * FROM " . $table, $table);
            }
            break;
    }

} else {
    print $PMF_LANG['err_NotAuth'];
}