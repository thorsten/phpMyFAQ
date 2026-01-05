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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use RuntimeException;
use SodiumException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

use function in_array;

final class BackupController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/backup', name: 'admin.backup', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::BACKUP);

        return $this->render(file: '@admin/backup/main.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderBackup' => Translation::get(key: 'msgBackup'),
            'adminBackupCardHeader' => Translation::get(key: 'ad_csv_head'),
            'adminBackupCardBody' => Translation::get(key: 'ad_csv_make'),
            'adminBackupLinkData' => Translation::get(key: 'ad_csv_linkdat'),
            'adminBackupLinkLogs' => Translation::get(key: 'ad_csv_linklog'),
            'csrfToken' => Token::getInstance($this->session)->getTokenString(page: 'restore'),
            'adminRestoreCardHeader' => Translation::get(key: 'ad_csv_head2'),
            'adminRestoreCardBody' => Translation::get(key: 'ad_csv_restore'),
            'adminRestoreLabel' => Translation::get(key: 'ad_csv_file'),
            'adminRestoreButton' => Translation::get(key: 'ad_csv_ok'),
        ]);
    }

    #[Route(path: '/backup/export/:type', name: 'admin.backup.export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $this->userHasPermission(PermissionType::BACKUP);

        $type = $request->attributes->get(key: 'type');
        if (!in_array($type, ['content', 'logs'], strict: true)) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::BACKUP_EXPORT->value);

        $backup = $this->container->get(id: 'phpmyfaq.backup');

        $backupType = $type === 'content' ? BackupType::BACKUP_TYPE_DATA : BackupType::BACKUP_TYPE_LOGS;

        try {
            $result = $backup->export($backupType);
        } catch (SodiumException) {
            return new Response(status: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = new Response($result->content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            urlencode((string) $result->fileName),
        );

        $response->headers->set(key: 'Content-Type', values: 'application/octet-stream; charset=UTF-8');
        $response->headers->set(key: 'Content-Disposition', values: $disposition);

        return $response;
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/backup/restore', name: 'admin.backup.restore', methods: ['POST'])]
    public function restore(Request $request): Response
    {
        $this->userHasPermission(PermissionType::RESTORE);

        $csrfToken = $request->query->get(key: 'csrf');
        if (!Token::getInstance($this->session)->verifyToken(page: 'restore', requestToken: $csrfToken)) {
            throw new UnauthorizedHttpException(challenge: 'Invalid CSRF token');
        }

        $this->adminLog->log($this->currentUser, AdminLogType::BACKUP_RESTORE->value);

        $file = $request->files->get(key: 'userfile');

        if (!$file) {
            throw new RuntimeException(message: 'No file uploaded');
        }

        $templateVars = [
            'adminHeaderRestore' => Translation::get(key: 'ad_csv_rest'),
        ];

        if (!$file->isValid()) {
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
                'errorMessageUpload' => Translation::get(key: 'ad_csv_no'),
                'errorMessageUploadDetails' => $errorMessage,
            ];

            return $this->render(file: '@admin/backup/import.twig', context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ]);
        }

        $backup = $this->container->get(id: 'phpmyfaq.backup');

        $fileName = $file->getClientOriginalName();

        try {
            $verification = $backup->verifyBackup(file_get_contents($file->getPathname()), $fileName);
            if (!$verification) {
                $templateVars = [
                    ...$templateVars,
                    'errorMessageNoVerification' => 'This file is not a verified backup file.',
                ];

                return $this->render(file: '@admin/backup/import.twig', context: [
                    ...$this->getHeader($request),
                    ...$this->getFooter(),
                    ...$templateVars,
                ]);
            }
        } catch (SodiumException) {
            $templateVars = ['errorMessageNoVerification' => 'This file cannot be verified.'];

            return $this->render(file: '@admin/backup/import.twig', context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ]);
        }

        try {
            $parseResult = $backup->parseBackupFile($file->getPathname(), $this->configuration->getVersion());
        } catch (Exception $exception) {
            $templateVars = [
                ...$templateVars,
                'errorMessageImportNotPossible' => $exception->getMessage(),
            ];

            return $this->render(file: '@admin/backup/import.twig', context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ]);
        }

        if (!$parseResult->versionMatches) {
            $templateVars = [
                ...$templateVars,
                'errorMessageVersionMisMatch' => sprintf(
                    '%s (Version check failure: "%s" found, "%s" expected)',
                    Translation::get(key: 'ad_csv_no'),
                    $parseResult->versionFound,
                    $parseResult->versionExpected,
                ),
            ];

            return $this->render(file: '@admin/backup/import.twig', context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ]);
        }

        $templateVars = [
            ...$templateVars,
            'prepareMessage' => Translation::get(key: 'ad_csv_prepare'),
        ];

        $executeResult = $backup->executeBackupQueries($parseResult->queries, $parseResult->tablePrefix);

        $templateVars = [
            ...$templateVars,
            'processMessage' => Translation::get(key: 'ad_csv_process'),
        ];

        if ($executeResult->queriesFailed > 0) {
            $templateVars = [
                ...$templateVars,
                'errorMessageQueryFailed' => sprintf(
                    '<strong>Query</strong>: "%s" failed (Reason: %s)',
                    Strings::htmlspecialchars((string) $executeResult->lastErrorQuery, ENT_QUOTES),
                    $executeResult->lastErrorReason,
                ),
                'errorMessageImportNotPossible' => Translation::get(key: 'ad_csv_no'),
            ];

            return $this->render(file: '@admin/backup/import.twig', context: [
                ...$this->getHeader($request),
                ...$this->getFooter(),
                ...$templateVars,
            ]);
        }

        $templateVars = [
            ...$templateVars,
            'successMessage' => sprintf(
                '%d %s %d %s',
                $executeResult->queriesOk,
                Translation::get(key: 'ad_csv_of'),
                $executeResult->queriesOk,
                Translation::get(key: 'ad_csv_suc'),
            ),
        ];

        return $this->render(file: '@admin/backup/import.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$templateVars,
        ]);
    }
}
