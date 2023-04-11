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
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_SPECIAL_CHARS);
$attId = Filter::filterInput(INPUT_GET, 'attId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_POST, 'record_id', FILTER_SANITIZE_SPECIAL_CHARS);
$recordLang = Filter::filterInput(INPUT_POST, 'record_lang', FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $attachment = AttachmentFactory::create($attId);

    switch ($ajaxAction) {
        case 'delete':
            if (!Token::getInstance()->verifyToken('delete-attachment', $csrfToken)) {
                $http->setStatus(401);
                echo Translation::get('err_NotAuth');
                exit(1);
            }

            if ($attachment->delete()) {
                $http->setStatus(200);
                echo Translation::get('msgAttachmentsDeleted');
            } else {
                $http->setStatus(400);
                echo Translation::get('ad_att_delfail');
            }
            break;

        case 'upload':
            if (!isset($_FILES['filesToUpload'])) {
                $http->setStatus(400);
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
                    $http->setStatus(400);
                    $http->sendJsonWithHeaders('The image is too large.');
                    return;
                }
            }

            $http->setStatus(200);
            $http->sendJsonWithHeaders($uploadedFiles);
            break;
    }
} catch (AttachmentException $e) {
    // handle exception
} catch (JsonException $e) {
    // handle exception
}
