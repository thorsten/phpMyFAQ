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
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Logging;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_UNSAFE_RAW);
$csrfTokenPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
$csrfTokenGet = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

$csrfToken = (is_null($csrfTokenPost) ? $csrfTokenGet : $csrfTokenPost);

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$items = isset($_GET['items']) && is_array($_GET['items']) ? $_GET['items'] : [];

if (!isset($items[0][2])) {
    $items[0][2] = 0;
}

switch ($ajaxAction) {
    // Get permissions
    case 'permissions':
        $faqId = Filter::filterInput(INPUT_GET, 'faq-id', FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission($faqConfig);

        $http->setStatus(200);
        $http->sendJsonWithHeaders(
            [
                'user' => $faqPermission->get(FaqPermission::USER, $faqId),
                'group' => $faqPermission->get(FaqPermission::GROUP, $faqId)
            ]
        );
        break;

    // save active FAQs
    case 'save_active_records':
        if ($user->perm->hasPermission($user->getUserId(), 'approverec')) {
            if (!empty($items)) {
                $faq = new Faq($faqConfig);

                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && Language::isASupportedLanguage($item[1])) {
                        echo $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), $item[2], 'active');
                    }
                }
            }
        } else {
            echo Translation::get('err_NotAuth');
        }
        break;

    // save sticky FAQs
    case 'save_sticky_records':
        if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
            if (!empty($items)) {
                $faq = new Faq($faqConfig);

                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && Language::isASupportedLanguage($item[1])) {
                        echo $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), $item[2], 'sticky');
                    }
                }
            }
        } else {
            echo Translation::get('err_NotAuth');
        }
        break;

    // search FAQs for suggestions
    case 'search_records':
        if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
            $faqPermission = new FaqPermission($faqConfig);
            $faqSearch = new Search($faqConfig);
            $faqSearch->setCategory(new Category($faqConfig));
            $faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
            $searchResult = '';
            $searchString = Filter::filterInput(INPUT_POST, 'search', FILTER_UNSAFE_RAW);

            if (!is_null($searchString)) {
                $searchResult = $faqSearch->search($searchString, false);

                $faqSearchResult->reviewResultSet($searchResult);

                $searchHelper = new SearchHelper($faqConfig);
                $searchHelper->setSearchTerm($searchString);

                echo $searchHelper->renderAdminSuggestionResult($faqSearchResult);
            }
        } else {
            echo Translation::get('err_NotAuth');
        }
        break;

    // delete FAQs
    case 'delete_record':
        $deleteData = json_decode(file_get_contents('php://input', true));

        if (
            $user->perm->hasPermission($user->getUserId(), 'delete_faq') &&
            Token::getInstance()->verifyToken('faq-overview', $deleteData->csrf)
        ) {
            $recordId = Filter::filterVar($deleteData->record_id, FILTER_VALIDATE_INT);
            $recordLang = Filter::filterVar($deleteData->record_lang, FILTER_UNSAFE_RAW);

            $logging = new Logging($faqConfig);
            $logging->logAdmin($user, 'Deleted FAQ ID ' . $recordId);

            try {
                $faq->deleteRecord($recordId, $recordLang);
            } catch (FileException | AttachmentException $e) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $e->getMessage()]);
            }
            $http->setStatus(200);
            $http->sendJsonWithHeaders(['success' => Translation::get('ad_entry_delsuc') ]);
        } else {
            $http->setStatus(401);
            $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
        }
        break;

    // delete open questions
    case 'delete_question':
        $deleteData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('delete-questions', $deleteData->data->{'pmf-csrf-token'})) {
            $http->setStatus(401);
            $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
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
                    #$question->deleteQuestion((int)$questionId);
                }

                $http->setStatus(200);
                $http->sendJsonWithHeaders(['success' => Translation::get('ad_entry_delsuc')]);
            } else {
                $http->setStatus(401);
                $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
            }
        }
        break;
}
