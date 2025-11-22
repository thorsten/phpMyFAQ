<?php

/**
 * The Administration Backup Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use SodiumException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class BackupController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/backup', name: 'admin.backup', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::BACKUP);

        return $this->render('@admin/backup/main.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderBackup' => Translation::get(languageKey: 'msgBackup'),
            'adminBackupCardHeader' => Translation::get(languageKey: 'ad_csv_head'),
            'adminBackupCardBody' => Translation::get(languageKey: 'ad_csv_make'),
            'adminBackupLinkData' => Translation::get(languageKey: 'ad_csv_linkdat'),
            'adminBackupLinkLogs' => Translation::get(languageKey: 'ad_csv_linklog'),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString('restore'),
            'adminRestoreCardHeader' => Translation::get(languageKey: 'ad_csv_head2'),
            'adminRestoreCardBody' => Translation::get(languageKey: 'ad_csv_restore'),
            'adminRestoreLabel' => Translation::get(languageKey: 'ad_csv_file'),
            'adminRestoreButton' => Translation::get(languageKey: 'ad_csv_ok'),
        ]);
    }

    #[Route('/backup/export/:type', name: 'admin.backup.export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $this->userHasPermission(PermissionType::BACKUP);

        $type = $request->get('type');
        $backup = $this->container->get(id: 'phpmyfaq.backup');

        switch ($type) {
            case 'content':
                $tableNames = $backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);
                break;
            case 'logs':
                $tableNames = $backup->getBackupTableNames(BackupType::BACKUP_TYPE_LOGS);
                break;
        }

        switch ($type) {
            case 'content':
                $backupQueries = $backup->generateBackupQueries($tableNames);
                try {
                    $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);

                    $response = new Response($backupQueries);

                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        urlencode($backupFileName),
                    );

                    $response->headers->set('Content-Type', 'application/octet-stream; charset=UTF-8');
                    $response->headers->set('Content-Disposition', $disposition);

                    return $response;
                } catch (SodiumException) {
                    // Handle exception
                }

                break;
            case 'logs':
                $backupQueries = $backup->generateBackupQueries($tableNames);
                try {
                    $backupFileName = $backup->createBackup(BackupType::BACKUP_TYPE_LOGS->value, $backupQueries);

                    $response = new Response($backupQueries);

                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        urlencode($backupFileName),
                    );

                    $response->headers->set('Content-Type', 'application/octet-stream; charset=UTF-8');
                    $response->headers->set('Content-Disposition', $disposition);

                    return $response;
                } catch (SodiumException) {
                    // Handle exception
                }

                break;
        }
    }

    /**
     * @throws \Exception
     * @todo: Refactor this method
     */
    #[Route('/backup/restore', name: 'admin.backup.restore', methods: ['POST'])]
    public function restore(Request $request): Response
    {
        $this->userHasPermission(PermissionType::RESTORE);

        $csrfToken = $request->query->get('csrf');
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('restore', $csrfToken)) {
            throw new UnauthorizedHttpException('Invalid CSRF token');
        }

        $file = $request->files->get('userfile');

        $templateVars = [
            'adminHeaderRestore' => Translation::get(languageKey: 'ad_csv_rest'),
        ];

        if ($file && $file->isValid()) {
            $backup = $this->container->get(id: 'phpmyfaq.backup');

            $handle = fopen($file->getPathname(), 'r');
            $backupData = fgets($handle, 65536);
            $versionFound = Strings::substr($backupData, 0, 9);
            $versionExpected = '-- pmf' . substr($this->configuration->getVersion(), 0, 3);
            $queries = [];

            $fileName = $file->getClientOriginalName();

            try {
                $verification = $backup->verifyBackup(file_get_contents($file->getPathname()), $fileName);
                if ($verification) {
                    $ok = 1;
                } else {
                    $templateVars = [
                        ...$templateVars,
                        'errorMessageNoVerification' => 'This file is not a verified backup file.',
                    ];
                    $ok = 0;
                }
            } catch (SodiumException) {
                $templateVars = ['errorMessageNoVerification' => 'This file cannot be verified.'];
                $ok = 0;
            }

            if ($versionFound !== $versionExpected) {
                $templateVars = [
                    ...$templateVars,
                    'errorMessageVersionMisMatch' => sprintf(
                        '%s (Version check failure: "%s" found, "%s" expected)',
                        Translation::get(languageKey: 'ad_csv_no'),
                        $versionFound,
                        $versionExpected,
                    ),
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
                    'prepareMessage' => Translation::get(languageKey: 'ad_csv_prepare'),
                ];
                while ($backupData = fgets($handle, 65536)) {
                    $backupData = trim($backupData);
                    $backupPrefixPattern = '-- pmftableprefix:';
                    $backupPrefixPatternLength = Strings::strlen($backupPrefixPattern);
                    if (Strings::substr($backupData, 0, $backupPrefixPatternLength) === $backupPrefixPattern) {
                        $tablePrefix = trim(Strings::substr($backupData, $backupPrefixPatternLength));
                    }

                    if (Strings::substr($backupData, 0, 2) !== '--' && $backupData !== '') {
                        $queries[] = trim(Strings::substr($backupData, 0, -1));
                    }
                }

                $k = 0;
                $g = 0;

                $templateVars = [
                    ...$templateVars,
                    'processMessage' => Translation::get(languageKey: 'ad_csv_process'),
                ];

                $numTables = count($queries);
                $kg = '';
                for ($i = 0; $i < $numTables; ++$i) {
                    $queries[$i] = DatabaseHelper::alignTablePrefix(
                        $queries[$i],
                        $tablePrefix,
                        Database::getTablePrefix(),
                    );

                    $kg = $this->configuration->getDb()->query($queries[$i]);
                    if (!$kg) {
                        $templateVars = [
                            ...$templateVars,
                            'errorMessageQueryFailed' => sprintf(
                                '<strong>Query</strong>: "%s" failed (Reason: %s)',
                                Strings::htmlspecialchars($queries[$i], ENT_QUOTES),
                                $this->configuration->getDb()->error(),
                            ),
                        ];

                        ++$k;
                    } else {
                        printf(
                            '<!-- Query: "%s" okay</div> -->%s',
                            Strings::htmlspecialchars($queries[$i], ENT_QUOTES),
                            "\n",
                        );
                        ++$g;
                    }
                }

                $templateVars = [
                    ...$templateVars,
                    'successMessage' => sprintf(
                        '%d %s %d %s',
                        $g,
                        Translation::get(languageKey: 'ad_csv_of'),
                        $numTables,
                        Translation::get(languageKey: 'ad_csv_suc'),
                    ),
                ];
            } else {
                $templateVars = ['errorMessageImportNotPossible' => Translation::get(languageKey: 'ad_csv_no')];
            }
        } else {
            $errorMessage = match ($file->getError()) {
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                4 => 'No file was uploaded.',
                6 => 'Missing a temporary folder.',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.',
                default => 'Undefined error.',
            };
            $templateVars = [
                ...$templateVars,
                'errorMessageUpload' => Translation::get(languageKey: 'ad_csv_no'),
                'errorMessageUploadDetails' => $errorMessage,
            ];
        }

        return $this->render('@admin/backup/import.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$templateVars,
        ]);
    }
}
