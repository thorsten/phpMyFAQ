<?php

/**
 * The export function to import the phpMyFAQ backups.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-18
 */

use phpMyFAQ\Backup;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_UNSAFE_RAW);

[ $user, $auth ] = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), 'backup')) {
    $tables = $faqConfig->getDb()->getTableNames(Database::getTablePrefix());
    $tableNames = '';

    $dbHelper = new DatabaseHelper($faqConfig);
    $backup = new Backup($faqConfig, $dbHelper);

    $httpHelper = new HttpHelper();
    $httpHelper->addHeader();
    $httpHelper->addExtraHeader('Content-Type: application/octet-stream');

    switch ($action) {
        case 'backup_content':
            foreach ($tables as $table) {
                if (
                    (Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)) || (Database::getTablePrefix(
                    ) . 'faqsessions' === trim((string) $table))
                ) {
                    continue;
                }
                $tableNames .= $table . ' ';
            }
            break;
        case 'backup_logs':
            foreach ($tables as $table) {
                if (
                    (Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)) || (Database::getTablePrefix(
                    ) . 'faqsessions' === trim((string) $table))
                ) {
                    $tableNames .= $table . ' ';
                }
            }
            break;
    }

    switch ($action) {
        case 'backup_content':
            $backupQueries = $backup->generateBackupQueries($tableNames);
            try {
                $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);
                $header = sprintf('Content-Disposition: attachment; filename=%s', urlencode($backupFileName));
                $httpHelper->addExtraHeader($header);

                echo $backupQueries;
            } catch (SodiumException) {
                // Handle exception
            }
            break;
        case 'backup_logs':
            $backupQueries = $backup->generateBackupQueries($tableNames);
            try {
                $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_LOGS->value, $backupQueries);
                $header = sprintf('Content-Disposition: attachment; filename=%s', urlencode($backupFileName));
                $httpHelper->addExtraHeader($header);

                echo $backupQueries;
            } catch (SodiumException) {
                // Handle exception
            }
            break;
    }
} else {
    echo Translation::get('err_NotAuth');
}
