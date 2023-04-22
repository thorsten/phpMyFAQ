<?php

/**
 * Private phpMyFAQ Admin API: handles an attachment with the given id.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-20
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$attId = Filter::filterVar($request->query->get('attId'), FILTER_VALIDATE_INT);
$recordId = Filter::filterVar($request->request->get('record_id'), FILTER_SANITIZE_SPECIAL_CHARS);
$recordLang = Filter::filterVar($request->request->get('record_lang'), FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

switch ($ajaxAction) {
    case 'delete':
        $deleteData = json_decode(file_get_contents('php://input', true));
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
        $response->send();
        break;

    case 'upload':
        if (!isset($_FILES['filesToUpload'])) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return;
        }

        $files = AttachmentFactory::rearrangeUploadedFiles($_FILES['filesToUpload']);
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (
                is_uploaded_file($file['tmp_name']) &&
                !($file['size'] > $faqConfig->get('records.maxAttachmentSize')) &&
                $file['type'] !== "text/html"
            ) {
                $attachment = AttachmentFactory::create();
                $attachment->setRecordId($recordId);
                $attachment->setRecordLang($recordLang);
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
                    'faqId' => $recordId,
                    'faqLanguage' => $recordLang
                ];
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData('The image is too large.');
                $response->send();
                return;
            }
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($uploadedFiles);
        $response->send();
        break;
}
