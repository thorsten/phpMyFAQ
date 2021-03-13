<?php

/**
 * The export function to import the phpMyFAQ backups.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-08-18
 */

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

$auth = false;
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if ($user) {
    $auth = true;
} else {
    $user = null;
    unset($user);
}

if ($user->perm->hasPermission($user->getUserId(), 'backup')) {
    $tables = $tableNames = $faqConfig->getDb()->getTableNames(Database::getTablePrefix());
    $tablePrefix = (Database::getTablePrefix() !== '') ? Database::getTablePrefix() . '.phpmyfaq' : 'phpmyfaq';
    $tableNames = '';
    $majorVersion = substr($faqConfig->getVersion(), 0, 3);
    $dbHelper = new DatabaseHelper($faqConfig);
    $httpHelper = new HttpHelper();
    $httpHelper->addHeader();
    $httpHelper->addAdditionalHeader('Content-Type: application/octet-stream');

    switch ($action) {
        case 'backup_content':
            foreach ($tables as $table) {
                if (
                    (Database::getTablePrefix() . 'faqadminlog' === trim($table)) || (Database::getTablePrefix(
                    ) . 'faqsessions' === trim($table))
                ) {
                    continue;
                }
                $tableNames .= $table . ' ';
            }
            break;
        case 'backup_logs':
            foreach ($tables as $table) {
                if (
                    (Database::getTablePrefix() . 'faqadminlog' === trim($table)) || (Database::getTablePrefix(
                    ) . 'faqsessions' === trim($table))
                ) {
                    $tableNames .= $table . ' ';
                }
            }
            break;
    }

    $text[] = '-- pmf' . $majorVersion . ': ' . $tableNames;
    $text[] = '-- DO NOT REMOVE THE FIRST LINE!';
    $text[] = '-- pmftableprefix: ' . Database::getTablePrefix();
    $text[] = '-- DO NOT REMOVE THE LINES ABOVE!';
    $text[] = '-- Otherwise this backup will be broken.';

    switch ($action) {
        case 'backup_content':
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
            $httpHelper->addAdditionalHeader($header);
            foreach (explode(' ', $tableNames) as $table) {
                echo implode("\r\n", $text);
                if ('' !== $table) {
                    $text = $dbHelper->buildInsertQueries('SELECT * FROM ' . $table, $table);
                }
            }
            break;
        case 'backup_logs':
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
            $httpHelper->addAdditionalHeader($header);
            foreach (explode(' ', $tableNames) as $table) {
                echo implode("\r\n", $text);
                if ('' !== $table) {
                    $text = $dbHelper->buildInsertQueries('SELECT * FROM ' . $table, $table);
                }
            }
            break;
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
