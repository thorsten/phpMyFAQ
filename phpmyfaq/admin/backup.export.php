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
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-18
 */

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

//
// Create Request & Response
//
$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();

$action = Filter::filterVar($request->query->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);

$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::BACKUP->value)) {
    $dbHelper = new DatabaseHelper($faqConfig);
    $backup = new Backup($faqConfig, $dbHelper);

    switch ($action) {
        case 'backup_content':
            $tableNames = $backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);
            break;
        case 'backup_logs':
            $tableNames = $backup->getBackupTableNames(BackupType::BACKUP_TYPE_LOGS);
            break;
    }

    switch ($action) {
        case 'backup_content':
            $backupQueries = $backup->generateBackupQueries($tableNames);
            try {
                $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);

                $response = new Response($backupQueries);

                $disposition = HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    urlencode($backupFileName)
                );

                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', $disposition);

                $response->send();
            } catch (SodiumException) {
                // Handle exception
            }
            break;
        case 'backup_logs':
            $backupQueries = $backup->generateBackupQueries($tableNames);
            try {
                $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_LOGS->value, $backupQueries);

                $response = new Response($backupQueries);

                $disposition = HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    urlencode($backupFileName)
                );

                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', $disposition);

                $response->send();
            } catch (SodiumException) {
                // Handle exception
            }
            break;
    }
} else {
    require __DIR__ . '/no-permission.php';
}
