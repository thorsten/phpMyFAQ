<?php

/**
 * The Admin Attachment Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AttachmentController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/content/attachments', name: 'admin.api.content.attachments', methods: ['GET'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $deleteData = json_decode($request->getContent());
        try {
            if (!Token::getInstance($this->session)->verifyToken('delete-attachment', $deleteData->csrf)) {
                return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
            }

            $attachment = AttachmentFactory::create($deleteData->attId);
            if ($attachment->delete()) {
                return $this->json(['success' => Translation::get(key: 'msgAttachmentsDeleted')], Response::HTTP_OK);
            }

            return $this->json(['error' => Translation::get(key: 'ad_att_delfail')], Response::HTTP_BAD_REQUEST);
        } catch (AttachmentException $attachmentException) {
            $result = ['error' => $attachmentException->getMessage()];
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(
        './admin/api/content/attachments/refresh',
        name: 'admin.api.content.attachments.refresh',
        methods: ['POST'],
    )]
    public function refresh(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $dataToCheck = json_decode($request->getContent());
        try {
            if (!Token::getInstance($this->session)->verifyToken('refresh-attachment', $dataToCheck->csrf)) {
                return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
            }

            $attachment = AttachmentFactory::create($dataToCheck->attId);
            if (!$attachment->isStorageOk()) {
                $attachment->deleteMeta();
                $result = ['success' => Translation::get(key: 'ad_att_delsuc'), 'delete' => true];
            } else {
                $result = [
                    'success' => Translation::get(key: 'msgAdminAttachmentRefreshed'),
                    'delete' => false,
                ];
            }

            return $this->json($result, Response::HTTP_OK);
        } catch (AttachmentException $attachmentException) {
            $result = ['error' => $attachmentException->getMessage()];
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws AttachmentException
     * @throws FileException
     * @throws Exception
     * @throws \Exception
     */
    #[Route(
        path: './admin/api/content/attachments/upload',
        name: 'admin.api.content.attachments.upload',
        methods: ['POST'],
    )]
    public function upload(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_ADD);

        $files = $request->files->get('filesToUpload');

        if (!$files) {
            return $this->json(['error' => Translation::get(key: 'msgNoImagesForUpload')], Response::HTTP_BAD_REQUEST);
        }

        $uploadedFiles = [];

        foreach ($files as $file) {
            if (
                $file->isValid()
                && $file->getSize() <= $this->configuration->get(item: 'records.maxAttachmentSize')
                && $file->getMimeType() !== 'text/html'
            ) {
                $attachment = AttachmentFactory::create();
                $attachment->setRecordId($request->attributes->get('record_id'));
                $attachment->setRecordLang($request->attributes->get('record_lang'));
                try {
                    if (!$attachment->save($file->getPathname(), $file->getClientOriginalName())) {
                        return $this->json(['error' => Translation::get(
                            'msgImageCouldNotBeUploaded',
                        )], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                } catch (AttachmentException|FileNotFoundException $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $uploadedFiles[] = [
                    'attachmentId' => $attachment->getId(),
                    'fileName' => $attachment->getFilename(),
                    'faqId' => $request->attributes->get('record_id'),
                    'faqLanguage' => $request->attributes->get('record_lang'),
                ];
            } else {
                return $this->json(['error' => Translation::get(key: 'msgImageTooLarge')], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json($uploadedFiles, Response::HTTP_OK);
    }
}
