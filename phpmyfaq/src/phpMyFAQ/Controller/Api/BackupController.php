<?php

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use SodiumException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.0/backup/{type}',
        operationId: 'createBackup',
        tags: ['Endpoints with Authentication'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'type',
        description: 'The backup type. Can be "data" or "logs".',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'The current backup as a file.',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string')
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'If the backup type is wrong',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string')
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated and/or does not have sufficient permissions.'
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
            default:
                return new Response('Invalid backup type.', Response::HTTP_BAD_REQUEST);
        }

        $dbHelper = new DatabaseHelper($this->configuration);
        $backup = new Backup($this->configuration, $dbHelper);
        $tableNames = $backup->getBackupTableNames($backupType);
        $backupQueries = $backup->generateBackupQueries($tableNames);

        try {
            $backupFileName = $backup->createBackup($backupType->value, $backupQueries);

            $response = new Response($backupQueries);

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                urlencode($backupFileName)
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
