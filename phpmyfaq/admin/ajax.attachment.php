<?php

/**
 * AJAX: handles an attachment with the given id.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-12-20
 */

use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Attachment\Exception;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Filter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$http = new HttpHelper();

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$attId = Filter::filterInput(INPUT_GET, 'attId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_POST, 'record_id', FILTER_SANITIZE_STRING);
$recordLang = Filter::filterInput(INPUT_POST, 'record_lang', FILTER_SANITIZE_STRING);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

try {
    $attachment = Factory::create($attId);

    switch ($ajaxAction) {
        case 'delete':

            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                echo $PMF_LANG['err_NotAuth'];
                exit(1);
            }

            if ($attachment->delete()) {
                echo $PMF_LANG['msgAttachmentsDeleted'];
            } else {
                echo $PMF_LANG['ad_att_delfail'];
            }
            break;

        case 'upload':

            if (!isset($_FILES['filesToUpload'])) {
                $http->sendStatus(400);
                return;
            }

            $files = Factory::rearrangeUploadedFiles($_FILES['filesToUpload']);
            $uploadedFiles = [];

            foreach ($files as $file) {
                if (
                    is_uploaded_file($file['tmp_name']) &&
                    !($file['size'] > $faqConfig->get('records.maxAttachmentSize')) &&
                    $file['type'] !== "text/html"
                ) {
                    $attachment = Factory::create();
                    $attachment->setRecordId($recordId);
                    $attachment->setRecordLang($recordLang);
                    try {
                        if (!$attachment->save($file['tmp_name'], $file['name'])) {
                            throw new Exception();
                        }
                    } catch (Exception $e) {
                        $attachment->delete();
                    }
                    $uploadedFiles[] = [
                        'attachmentId' => $attachment->getId(),
                        'fileName' => $attachment->getFilename(),
                        'faqId' => $recordId,
                        'faqLanguage' => $recordLang
                    ];
                } else {
                    $http->sendStatus(400);
                    $http->sendJsonWithHeaders('image too large');
                    return;
                }
            }

            $http->sendJsonWithHeaders($uploadedFiles);

            break;
    }
} catch (Exception $e) {
    // handle exception
}
