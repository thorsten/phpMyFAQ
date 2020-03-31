<?php

/**
 * AJAX: handling of Ajax record calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-03-31
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Category;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Logging;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$csrfTokenPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
$csrfTokenGet = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

$csrfToken = (is_null($csrfTokenPost) ? $csrfTokenGet : $csrfTokenPost);

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    echo $PMF_LANG['err_NotAuth'];
    exit(1);
}

$items = isset($_GET['items']) && is_array($_GET['items']) ? $_GET['items'] : [];

if (!isset($items[0][2])) {
    $items[0][2] = 0;
}

switch ($ajaxAction) {

    // Get permissions
    case 'permissions':
        $faqId = Filter::filterInput(INPUT_GET, 'faq-id', FILTER_VALIDATE_INT);
        $http = new HttpHelper();
        $http->setContentType('application/json');
        $http->addHeader();

        $faq = new Faq($faqConfig);
        $faq->setUser($currentAdminUser);
        $faq->setGroups($currentAdminGroups);

        $http->sendJsonWithHeaders(
            [
                'user' => $faq->getPermission('user', $faqId),
                'group' => $faq->getPermission('group', $faqId)
            ]
        );
        break;

    // save active FAQs
    case 'save_active_records':
        if ($user->perm->checkRight($user->getUserId(), 'approverec')) {
            if (!empty($items)) {
                $faq = new Faq($faqConfig);

                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && Language::isASupportedLanguage($item[1])) {
                        echo $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), $item[2], 'active');
                    }
                }
            }
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        break;

    // save sticky FAQs
    case 'save_sticky_records':
        if ($user->perm->checkRight($user->getUserId(), 'edit_faq')) {
            if (!empty($items)) {
                $faq = new Faq($faqConfig);

                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && Language::isASupportedLanguage($item[1])) {
                        echo $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), $item[2], 'sticky');
                    }
                }
            }
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        break;

    // search FAQs for suggestions
    case 'search_records':
        if ($user->perm->checkRight($user->getUserId(), 'edit_faq')) {
            $faq = new Faq($faqConfig);
            $faqSearch = new Search($faqConfig);
            $faqSearch->setCategory(new Category($faqConfig));
            $faqSearchResult = new SearchResultSet($user, $faq, $faqConfig);
            $searchResult = '';
            $searchString = Filter::filterInput(INPUT_POST, 'search', FILTER_SANITIZE_STRIPPED);

            if (!is_null($searchString)) {
                try {
                    $searchResult = $faqSearch->search($searchString, false);

                    $faqSearchResult->reviewResultSet($searchResult);

                    $searchHelper = new SearchHelper($faqConfig);
                    $searchHelper->setSearchterm($searchString);

                    echo $searchHelper->renderAdminSuggestionResult($faqSearchResult);
                } catch (Search\Exception $e) {
                    //
                }
            }
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        break;

    // delete FAQs
    case 'delete_record':
        if ($user->perm->checkRight($user->getUserId(), 'delete_faq')) {
            $recordId = Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
            $recordLang = Filter::filterInput(INPUT_POST, 'record_lang', FILTER_SANITIZE_STRING);

            $logging = new Logging($faqConfig);
            $logging->logAdmin($user, 'Deleted FAQ ID ' . $recordId);

            try {
                $faq->deleteRecord($recordId, $recordLang);
            } catch (FileException $e) {
            } catch (AttachmentException $e) {
            }
            echo $PMF_LANG['ad_entry_delsuc'];
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        break;

    // delete open questions
    case 'delete_question':
        if ($user->perm->checkRight($user->getUserId(), 'delquestion')) {
            $checks = [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ];
            $questionIds = Filter::filterInputArray(INPUT_POST, array('questions' => $checks));
            $question = new Question($faqConfig);

            if (!is_null($questionIds['questions'])) {
                foreach ($questionIds['questions'] as $questionId) {
                    $question->deleteQuestion((int)$questionId);
                }
            }
            echo $PMF_LANG['ad_entry_delsuc'];
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        break;
}
