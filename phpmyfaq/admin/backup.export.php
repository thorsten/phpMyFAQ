<?php

/**
 * The export function to import the phpMyFAQ backups.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-18
 */
define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/inc/Bootstrap.php';

$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

$auth = false;
$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}
if ($user) {
    $auth = true;
} else {
    $user = null;
    unset($user);
}

header('Content-Type: application/octet-stream');
header('Pragma: no-cache');

if ($user->perm->checkRight($user->getUserId(), 'backup')) {
    $tables = $tableNames = $faqConfig->getDb()->getTableNames(PMF_Db::getTablePrefix());
    $tablePrefix = (PMF_Db::getTablePrefix() !== '') ? PMF_Db::getTablePrefix().'.phpmyfaq' : 'phpmyfaq';
    $tableNames = '';
    $majorVersion = substr($faqConfig->get('main.currentVersion'), 0, 3);
    $dbHelper = new PMF_DB_Helper($faqConfig);

    switch ($action) {
        case 'backup_content' :
            foreach ($tables as $table) {
                if ((PMF_Db::getTablePrefix().'faqadminlog' == trim($table)) || (PMF_Db::getTablePrefix().'faqsessions' == trim($table))) {
                    continue;
                }
                $tableNames .= $table.' ';
            }
            break;
        case 'backup_logs' :
            foreach ($tables as $table) {
                if ((PMF_Db::getTablePrefix().'faqadminlog' == trim($table)) || (PMF_Db::getTablePrefix().'faqsessions' == trim($table))) {
                    $tableNames .= $table.' ';
                }
            }
            break;
    }

    $text[] = '-- pmf'.$majorVersion.': '.$tableNames;
    $text[] = '-- DO NOT REMOVE THE FIRST LINE!';
    $text[] = '-- pmftableprefix: '.PMF_Db::getTablePrefix();
    $text[] = '-- DO NOT REMOVE THE LINES ABOVE!';
    $text[] = '-- Otherwise this backup will be broken.';

    switch ($action) {
        case 'backup_content' :
            $header = sprintf(
                'Content-Disposition: attachment; filename=%s',
                urlencode(
                    sprintf(
                        '%s-data.%s.sql',
                        $tablePrefix,
                        date('Y-m-d-H-i-s')
                    )
                )
            );
            header($header);
            foreach (explode(' ', $tableNames) as $table) {
                print implode("\r\n", $text);
                $text = $dbHelper->buildInsertQueries('SELECT * FROM '.$table, $table);
            }
            break;
        case 'backup_logs' :
            $header = sprintf(
                'Content-Disposition: attachment; filename=%s',
                urlencode(
                    sprintf(
                        '%s-logs.%s.sql',
                        $tablePrefix,
                        date('Y-m-d-H-i-s')
                    )
                )
            );
            header($header);
            foreach (explode(' ', $tableNames) as $table) {
                print implode("\r\n", $text);
                $text = $dbHelper->buildInsertQueries('SELECT * FROM '.$table, $table);
            }
            break;
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
