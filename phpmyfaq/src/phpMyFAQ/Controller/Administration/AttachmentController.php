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
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AttachmentController extends AbstractController
{
    #[Route('./admin/api/content/attachments')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $response = new JsonResponse();
        $deleteData = json_decode($request->getContent());
        try {
            if (!Token::getInstance()->verifyToken('delete-attachment', $deleteData->csrf)) {
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
                $response->send();
                exit();
            }

            $attachment = AttachmentFactory::create($deleteData->attId);
            if ($attachment->delete()) {
                $response->setStatusCode(Response::HTTP_OK);
                $result = ['success' => Translation::get('msgAttachmentsDeleted')];
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $result = ['error' => Translation::get('ad_att_delfail')];
            }
        } catch (AttachmentException $e) {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $result = ['error' => $e->getMessage()];
        }
        $response->setData($result);

        return $response;
    }

    /**
     * @throws AttachmentException
     * @throws FileException
     */
    #[Route('./admin/api/content/attachments/upload')]
    public function upload(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_ADD);

        $response = new JsonResponse();

        if (!isset($_FILES['filesToUpload'])) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response;
        }

        $files = AttachmentFactory::rearrangeUploadedFiles($_FILES['filesToUpload']);
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (
                is_uploaded_file($file['tmp_name']) &&
                !($file['size'] > Configuration::getConfigurationInstance()->get('records.maxAttachmentSize')) &&
                $file['type'] !== "text/html"
            ) {
                $attachment = AttachmentFactory::create();
                $attachment->setRecordId($request->request->get('record_id'));
                $attachment->setRecordLang($request->request->get('record_lang'));
                try {
                    if (!$attachment->save($file['tmp_name'], $file['name'])) {
                        throw new AttachmentException();
                    }
                } catch (AttachmentException $e) {
                    $attachment->delete();
                }
                $uploadedFiles[] = [
                    'attachmentId' => $attachment->getId(),
                    'fileName' => $attachment->getFilename(),
                    'faqId' => $request->request->get('record_id'),
                    'faqLanguage' => $request->request->get('record_lang')
                ];
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData('The image is too large.');
                return $response;
            }
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($uploadedFiles);

        return $response;
    }
}
