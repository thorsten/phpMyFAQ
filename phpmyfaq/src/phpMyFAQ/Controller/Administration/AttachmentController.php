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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AttachmentController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('./admin/api/content/attachments')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $deleteData = json_decode($request->getContent());
        try {
            if (!Token::getInstance()->verifyToken('delete-attachment', $deleteData->csrf)) {
                return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
            }

            $attachment = AttachmentFactory::create($deleteData->attId);
            if ($attachment->delete()) {
                $result = ['success' => Translation::get('msgAttachmentsDeleted')];
                return $this->json($result, Response::HTTP_OK);
            } else {
                $result = ['error' => Translation::get('ad_att_delfail')];
                return $this->json($result, Response::HTTP_BAD_REQUEST);
            }
        } catch (AttachmentException $attachmentException) {
            $result = ['error' => $attachmentException->getMessage()];
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('./admin/api/content/attachments/refresh')]
    public function refresh(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $dataToCheck = json_decode($request->getContent());
        try {
            if (!Token::getInstance()->verifyToken('refresh-attachment', $dataToCheck->csrf)) {
                return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
            }

            $attachment = AttachmentFactory::create($dataToCheck->attId);
            if (!$attachment->isStorageOk()) {
                $attachment->deleteMeta();
                $result = ['success' => Translation::get('ad_att_delsuc'), 'delete' => true];
            } else {
                $result = ['success' => Translation::get('msgAdminAttachmentRefreshed'), 'delete' => false];
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
     */
    #[Route('./admin/api/content/attachments/upload')]
    public function upload(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_ADD);

        if (!isset($_FILES['filesToUpload'])) {
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }

        $files = AttachmentFactory::rearrangeUploadedFiles($_FILES['filesToUpload']);
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (
                is_uploaded_file($file['tmp_name']) &&
                $file['size'] <= Configuration::getConfigurationInstance()->get('records.maxAttachmentSize') &&
                $file['type'] !== "text/html"
            ) {
                $attachment = AttachmentFactory::create();
                $attachment->setRecordId($request->request->get('record_id'));
                $attachment->setRecordLang($request->request->get('record_lang'));
                try {
                    if (!$attachment->save($file['tmp_name'], $file['name'])) {
                        throw new AttachmentException();
                    }
                } catch (AttachmentException) {
                    $attachment->delete();
                }

                $uploadedFiles[] = [
                    'attachmentId' => $attachment->getId(),
                    'fileName' => $attachment->getFilename(),
                    'faqId' => $request->request->get('record_id'),
                    'faqLanguage' => $request->request->get('record_lang')
                ];
            } else {
                return $this->json('The image is too large.', Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json($uploadedFiles, Response::HTTP_OK);
    }
}
