<?php

/**
 * Private phpMyFAQ Admin API: handling of Ajax record calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-31
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Category;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\AdminLog;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
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
$csrfTokenPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
$csrfTokenGet = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

$csrfToken = (is_null($csrfTokenPost) ? $csrfTokenGet : $csrfTokenPost);

$items = isset($_GET['items']) && is_array($_GET['items']) ? $_GET['items'] : [];

if (!isset($items[0][2])) {
    $items[0][2] = 0;
}

switch ($ajaxAction) {
    // Get permissions
    case 'permissions':
        $faqId = Filter::filterInput(INPUT_GET, 'faq-id', FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission($faqConfig);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(
            [
                'user' => $faqPermission->get(FaqPermission::USER, $faqId),
                'group' => $faqPermission->get(FaqPermission::GROUP, $faqId)
            ]
        );
        $response->send();
        break;

    // save active FAQs
    case 'save_active_records':
        $postData = json_decode(file_get_contents('php://input', true));

        $faqIds = Filter::filterArray($postData->faqIds);
        $faqLanguage = Filter::filterVar($postData->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($postData->checked, FILTER_VALIDATE_BOOLEAN);

        if (
            $user->perm->hasPermission($user->getUserId(), 'approverec') &&
            Token::getInstance()->verifyToken('faq-overview', $postData->csrf)
        ) {
            if (!empty($faqIds)) {
                $faq = new Faq($faqConfig);

                foreach ($faqIds as $faqId) {
                    if (Language::isASupportedLanguage($faqLanguage)) {
                        $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'active');
                    }
                }
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => $success]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
        }
        $response->send();
        break;

    // save sticky FAQs
    case 'save_sticky_records':
        $postData = json_decode(file_get_contents('php://input', true));

        $faqIds = Filter::filterArray($postData->faqIds);
        $faqLanguage = Filter::filterVar($postData->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($postData->checked, FILTER_VALIDATE_BOOLEAN);

        if (
            $user->perm->hasPermission($user->getUserId(), 'edit_faq') &&
            Token::getInstance()->verifyToken('faq-overview', $postData->csrf)
        ) {
            if (!empty($faqIds)) {
                $faq = new Faq($faqConfig);
                foreach ($faqIds as $faqId) {
                    if (Language::isASupportedLanguage($faqLanguage)) {
                        $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'sticky');
                    }
                }
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => $success]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
        }
        $response->send();
        break;

    // search FAQs for suggestions
    case 'search_records':
        $postData = json_decode(file_get_contents('php://input', true));

        if (
            $user->perm->hasPermission($user->getUserId(), 'edit_faq') &&
            Token::getInstance()->verifyToken('edit-faq', $postData->csrf)
        ) {

            $faqPermission = new FaqPermission($faqConfig);
            $faqSearch = new Search($faqConfig);
            $faqSearch->setCategory(new Category($faqConfig));
            $faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
            $searchResult = '';
            $searchString = Filter::filterVar($postData->search, FILTER_SANITIZE_SPECIAL_CHARS);

            if (!is_null($searchString)) {
                $searchResult = $faqSearch->search($searchString, false);

                $faqSearchResult->reviewResultSet($searchResult);

                $searchHelper = new SearchHelper($faqConfig);
                $searchHelper->setSearchTerm($searchString);

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(
                    ['success' => $searchHelper->renderAdminSuggestionResult($faqSearchResult) ]
                );
            }
        } else {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
        }
        $response->send();
        break;

    // delete FAQs
    case 'delete_record':
        $deleteData = json_decode(file_get_contents('php://input', true));

        if (
            $user->perm->hasPermission($user->getUserId(), 'delete_faq') &&
            Token::getInstance()->verifyToken('faq-overview', $deleteData->csrf)
        ) {
            $recordId = Filter::filterVar($deleteData->record_id, FILTER_VALIDATE_INT);
            $recordLang = Filter::filterVar($deleteData->record_lang, FILTER_SANITIZE_SPECIAL_CHARS);

            $logging = new AdminLog($faqConfig);
            $logging->log($user, 'Deleted FAQ ID ' . $recordId);

            try {
                $faq->deleteRecord($recordId, $recordLang);
            } catch (FileException | AttachmentException $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $e->getMessage()]);
                $response->send();
            }
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_entry_delsuc') ]);
        } else {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
        }
        $response->send();
        break;

    // delete open questions
    case 'delete_question':
        $deleteData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('delete-questions', $deleteData->data->{'pmf-csrf-token'})) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            exit(1);
        }

        if ($user->perm->hasPermission($user->getUserId(), 'delquestion')) {
            $questionIds = $deleteData->data->{'questions[]'};
            $question = new Question($faqConfig);

            if (!is_null($questionIds)) {
                if (!is_array($questionIds)) {
                    $questionIds = [$questionIds];
                }
                foreach ($questionIds as $questionId) {
                    $question->deleteQuestion((int)$questionId);
                }

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('ad_open_question_deleted')]);
            } else {
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
            }
            $response->send();
        }
        break;
}
