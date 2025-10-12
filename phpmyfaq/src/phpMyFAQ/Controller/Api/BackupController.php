<?php

declare(strict_types=1);

/**
 * The Backup Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-24
 */

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use SodiumException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BackupController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    /**
     * @throws Exception
     */
    #[OA\Get(path: '/api/v3.1/backup/{type}', operationId: 'createBackup', tags: ['Endpoints with Authentication'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'type',
        description: 'The backup type. Can be "data", "logs" or "content".',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: 200,
        description: 'The current backup as a file or a ZipArchive in case of "content"-type.',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream or application/zip',
            schema: new OA\Schema(type: 'string'),
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'If the backup type is wrong or an internal error occurred',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string'),
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated and/or does not have sufficient permissions.',
    )]
    public function download(Request $request): Response
    {
        $this->userHasPermission(PermissionType::BACKUP);

        $type = Filter::filterVar($request->get('type'), FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($type) {
            case 'data':
                $backupType = BackupType::BACKUP_TYPE_DATA;
                break;
            case 'logs':
                $backupType = BackupType::BACKUP_TYPE_LOGS;
                break;
            case 'content':
                $backupType = BackupType::BACKUP_TYPE_CONTENT;
                break;
            default:
                return new Response('Invalid backup type.', Response::HTTP_BAD_REQUEST);
        }

        $databaseHelper = new DatabaseHelper($this->configuration);
        $backup = new Backup($this->configuration, $databaseHelper);

        // Create ZipArchive of content-folder
        if ($backupType === BackupType::BACKUP_TYPE_CONTENT) {
            $backupFile = $backup->createContentFolderBackup();
            $response = new Response(file_get_contents($backupFile));

            $backupFileName = sprintf('content_%s.zip', date('dmY_H-i'));

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                urlencode($backupFileName),
            );

            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', $disposition);
            $response->setStatusCode(Response::HTTP_OK);
            // Remove temporary ZipArchive
            unlink($backupFile);
            return $response->send();
        }

        $tableNames = $backup->getBackupTableNames($backupType);
        $backupQueries = $backup->generateBackupQueries($tableNames);

        try {
            $backupFileName = $backup->createBackup($backupType->value, $backupQueries);

            $response = new Response($backupQueries);

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                urlencode($backupFileName),
            );

            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', $disposition);
            $response->setStatusCode(Response::HTTP_OK);
            return $response->send();
        } catch (SodiumException) {
            return new Response('An error occurred while creating the backup.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
