<?php

/**
 * The import function to import the phpMyFAQ backups.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

if (
    $user->perm->hasPermission($user->getUserId(), 'restore') &&
    Token::getInstance()->verifyToken('restore', $csrfToken)
) {
    $twig = new TwigWrapper('./assets/templates');
    $template = $twig->loadTemplate('./backup/import.twig');

    $templateVars = [
        'adminHeaderRestore' => Translation::get('ad_csv_rest')
    ];

    if (isset($_FILES['userfile']) && 0 === $_FILES['userfile']['error']) {
        $ok = 1;
        $fileInfo = new finfo(FILEINFO_MIME_ENCODING);

        $dbHelper = new DatabaseHelper($faqConfig);
        $backup = new Backup($faqConfig, $dbHelper);

        if ('utf-8' !== $fileInfo->file($_FILES['userfile']['tmp_name'])) {
            $templateVars = [
                ...$templateVars,
                'errorMessageWrongEncoding' => 'This file is not UTF-8 encoded.'
            ];
            $ok = 0;
        }

        $handle = fopen($_FILES['userfile']['tmp_name'], 'r');
        $backupData = fgets($handle, 65536);
        $versionFound = Strings::substr($backupData, 0, 9);
        $versionExpected = '-- pmf' . substr($faqConfig->getVersion(), 0, 3);
        $queries = [];

        $fileName = $_FILES['userfile']['name'];

        try {
            $verification = $backup->verifyBackup(file_get_contents($_FILES['userfile']['tmp_name']), $fileName);
            if ($verification) {
                $ok = 1;
            } else {
                $templateVars = [
                    ...$templateVars,
                    'errorMessageNoVerification' => 'This file is not a verified backup file.'
                ];
                $ok = 0;
            }
        } catch (SodiumException) {
            $templateVars = [
                ...$templateVars,
                'errorMessageNoVerification' => 'This file cannot be verified.'
            ];
            $ok = 0;
        }

        if ($versionFound !== $versionExpected) {
            $templateVars = [
                ...$templateVars,
                'errorMessageVersionMisMatch' => sprintf(
                    '%s (Version check failure: "%s" found, "%s" expected)',
                    Translation::get('ad_csv_no'),
                    $versionFound,
                    $versionExpected
                )
            ];
            $ok = 0;
        }

        if ($ok === 1) {
            // @todo: Start transaction for better recovery if something really bad happens
            $backupData = trim(Strings::substr($backupData, 11));
            $tables = explode(' ', $backupData);
            $numTables = count($tables);
            for ($h = 0; $h < $numTables; ++$h) {
                $queries[] = sprintf('DELETE FROM %s', $tables[$h]);
            }
            $ok = 1;
        }

        if ($ok === 1) {
            $tablePrefix = '';
            $templateVars = [
                ...$templateVars,
                'prepareMessage' => Translation::get('ad_csv_prepare')
            ];
            while ($backupData = fgets($handle, 65536)) {
                $backupData = trim($backupData);
                $backupPrefixPattern = '-- pmftableprefix:';
                $backupPrefixPatternLength = Strings::strlen($backupPrefixPattern);
                if (Strings::substr($backupData, 0, $backupPrefixPatternLength) === $backupPrefixPattern) {
                    $tablePrefix = trim(Strings::substr($backupData, $backupPrefixPatternLength));
                }
                if ((Strings::substr($backupData, 0, 2) != '--') && ($backupData != '')) {
                    $queries[] = trim(Strings::substr($backupData, 0, -1));
                }
            }

            $k = 0;
            $g = 0;

            $templateVars = [
                ...$templateVars,
                'processMessage' => Translation::get('ad_csv_process')
            ];

            $numTables = count($queries);
            $kg = '';
            for ($i = 0; $i < $numTables; ++$i) {
                $queries[$i] = DatabaseHelper::alignTablePrefix($queries[$i], $tablePrefix, Database::getTablePrefix());

                $kg = $faqConfig->getDb()->query($queries[$i]);
                if (!$kg) {
                    $templateVars = [
                        ...$templateVars,
                        'errorMessageQueryFailed' => sprintf(
                            '<strong>Query</strong>: "%s" failed (Reason: %s)',
                            Strings::htmlspecialchars($queries[$i], ENT_QUOTES),
                            $faqConfig->getDb()->error()
                        )
                    ];

                    ++$k;
                } else {
                    printf(
                        '<!-- Query: "%s" okay</div> -->%s',
                        Strings::htmlspecialchars($queries[$i], ENT_QUOTES),
                        "\n"
                    );
                    ++$g;
                }
            }

            $templateVars = [
                ...$templateVars,
                'successMessage' => sprintf(
                    '%d %s %d %s',
                    $g,
                    Translation::get('ad_csv_of'),
                    $numTables,
                    Translation::get('ad_csv_suc')
                )
            ];
        } else {
            $templateVars = [
                ...$templateVars,
                'errorMessageImportNotPossible' => Translation::get('ad_csv_no')
            ];
        }
    } else {
        $errorMessage = match ($_FILES['userfile']['error']) {
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the ' . 'HTML form.',
            3 => 'The uploaded file was only partially uploaded.',
            4 => 'No file was uploaded.',
            6 => 'Missing a temporary folder.',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
            default => 'Undefined error.',
        };
        $templateVars = [
            ...$templateVars,
            'errorMessageUpload' => Translation::get('ad_csv_no'),
            'errorMessageUploadDetails' => $errorMessage
        ];
    }

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
