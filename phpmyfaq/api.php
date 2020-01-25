<?php

/**
 * The REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-09-03
 */

define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\News;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Services;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;

//
// Bootstrapping
//
require 'src/Bootstrap.php';

//
// Send headers
//
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

//
// Set user permissions
//
$auth = false;

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

$faqUsername = Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$faqPassword = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

//
// Get language (default: english)
//
$language = new Language($faqConfig);
$currentLanguage = $language->setLanguageByAcceptLanguage();

//
// Set language
//
if (Language::isASupportedLanguage($currentLanguage)) {
    require PMF_LANGUAGE_DIR . '/language_' . $currentLanguage . '.php';
} else {
    require PMF_LANGUAGE_DIR . '/language_en.php';
}
$faqConfig->setLanguage($language);

$plr = new Plurals($PMF_LANG);
Strings::init($currentLanguage);

//
// Set empty result
$result = [];

//
// Check if user is already authenticated
//
if (is_null($faqUsername) && is_null($faqPassword)) {
    $user = CurrentUser::getFromCookie($faqConfig);
    if (!$user instanceof CurrentUser) {
        $user = CurrentUser::getFromSession($faqConfig);
    }
    if ($user instanceof CurrentUser) {
        $auth = true;
    } else {
        $user = new CurrentUser($faqConfig);
    }
} else {
    $user = new CurrentUser($faqConfig);
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof CurrentUser) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = [-1];
    }
    if (0 == count($currentGroups)) {
        $currentGroups = [-1];
    }
} else {
    $currentUser = -1;
    $currentGroups = [-1];
}

//
// Handle actions
//
switch ($action) {

    //
    // v1 - @deprecated These APIs will be removed in phpMyFAQ 3.1
    //
    case 'getVersion':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $result = ['version' => $faqConfig->get('main.currentVersion')];
        break;

    case 'getApiVersion':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $result = ['apiVersion' => $faqConfig->get('main.currentApiVersion')];
        break;

    case 'getCount':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = ['faqCount' => $faq->getNumberOfRecords($currentLanguage)];
        break;

    case 'getDefaultLanguage':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $result = ['defaultLanguage' => $faqConfig->getLanguage()->getLanguage()];
        break;

    case 'getCategories':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $result = array_values($category->getAllCategories());
        break;

    case 'getPopularTags':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        break;

    case 'getPopularSearches':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        break;

    case 'getComments':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $comment = new Comments($faqConfig);
        $result = $comment->getCommentsData($recordId, 'faq');
        break;

    case 'getAttachmentsFromFaq':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $attachments = [];
        try {
            $attachments = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        } catch (AttachmentException $e) {
            $result = ['error' => $e->getMessage()];
        }
        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }
        break;

    case 'getNews':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $news = new News($faqConfig);
        $result = $news->getLatestData(false, true, true);
        break;

    case 'getFaqs':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        try {
            $result = $faq->getAllRecordPerCategory($categoryId);
        } catch (Exception $e) {
            // @todo Handle exception
        }
        break;

    case 'getAllFaqs':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $currentLanguage]);
        $result = $faq->faqRecords;
        break;

    case 'getPopular':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = array_values($faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN));
        break;

    case 'getLatest':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = array_values($faq->getLatestData(PMF_NUMBER_RECORDS_LATEST));
        break;

    case 'getFaq':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;

    case 'getFAQsByTag':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $tags = new Tags($faqConfig);
        $recordIds = $tags->getFaqsByTagId($tagId);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        try {
            $result = $faq->getRecordsByIds($recordIds);
        } catch (Exception $e) {
            // @todo Handle exception
        }
        break;

    case 'getFaqAsPdf':
        // @deprecated This API will be removed in phpMyFAQ 3.1
        $service = new Services($faqConfig);
        $service->setFaqId($recordId);
        $service->setLanguage($currentLanguage);
        $service->setCategoryId($categoryId);

        $result = ['pdfUrl' => $service->getPdfApiLink()];
        break;

    //
    // v2
    //
    case 'version':
        $result = $faqConfig->get('main.currentVersion');
        break;

    case 'language':
        $result = $faqConfig->getLanguage()->getLanguage();
        break;

    case 'search':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $user = new CurrentUser($faqConfig);
        $search = new Search($faqConfig);
        $search->setCategory(new Category($faqConfig));
        $faqSearchResult = new SearchResultSet($user, $faq, $faqConfig);

        $searchString = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        $searchResults = $search->search($searchString, false);
        $url = $faqConfig->getDefaultUrl() . 'index.php?action=faq&cat=%d&id=%d&artlang=%s';
        $faqSearchResult->reviewResultSet($searchResults);
        if ($faqSearchResult->getNumberOfResults() > 0) {
            foreach ($faqSearchResult->getResultSet() as $data) {
                $data->answer = html_entity_decode(strip_tags($data->answer), ENT_COMPAT, 'utf-8');
                $data->answer = Utils::makeShorterText($data->answer, 12);
                $data->link = sprintf($url, $data->category_id, $data->id, $data->lang);
                $result[] = $data;
            }
        } else {
            $http->setStatus(404);
        }

        break;

    case 'categories':
        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);
        $result = array_values($category->getAllCategories());
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'tags':
        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'open-questions':
        $questions = new Question($faqConfig);
        $result = $questions->getAllOpenQuestions();
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'searches':
        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'comments':
        $comment = new Comments($faqConfig);
        $result = $comment->getCommentsData($recordId, CommentType::FAQ);
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'attachments':
        $attachments = $result = [];
        try {
            $attachments = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        } catch (AttachmentException $e) {
            $result = [];
        }
        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'news':
        $news = new News($faqConfig);
        $result = $news->getLatestData(false, true, true);
        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;


    case 'faqs':
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        // api/v2.0/faqs/:categoryId
        if (!is_null($categoryId)) {
            try {
                $result = $faq->getAllRecordPerCategory($categoryId);
            } catch (Exception $e) {
                $http->setStatus(400);
            }
        }

        // api/v2.0/faqs/tags/:tagId
        if (!is_null($tagId)) {
            $tags = new Tags($faqConfig);
            $recordIds = $tags->getFaqsByTagId($tagId);
            try {
                $result = $faq->getRecordsByIds($recordIds);
            } catch (Exception $e) {
                $http->setStatus(400);
            }
        }

        // api/v2.0/faqs/popular
        if ('popular' === $filter) {
            $result = array_values($faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN));
        }

        // api/v2.0/faqs/latest
        if ('latest' === $filter) {
            $result = array_values($faq->getLatestData(PMF_NUMBER_RECORDS_LATEST));
        }

        // api/v2.0/faqs/sticky
        if ('sticky' === $filter) {
            $result = array_values($faq->getStickyRecordsData());
        }

        // api/v2.0/faqs
        if (is_null($categoryId) && is_null($tagId) && is_null($filter)) {
            $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $currentLanguage]);
            $result = $faq->faqRecords;
        }

        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;


    case 'faq':
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);

        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;

        if (count($result) === 0 || $result['solution_id'] === 42) {
            $result = new stdClass();
            $http->setStatus(404);
        }

        if ('pdf' === $filter) {
            $service = new Services($faqConfig);
            $service->setFaqId($recordId);
            $service->setLanguage($currentLanguage);
            $service->setCategoryId($categoryId);

            $result = $service->getPdfApiLink();
        }
        break;

    case 'login':
        $currentUser = new CurrentUser($faqConfig);

        if ($currentUser->login($faqUsername, $faqPassword)) {
            if ($currentUser->getStatus() !== 'blocked') {
                $auth = true;
                $result = [
                    'loggedin' => true
                ];
            } else {
                $auth = false;
                $http->setStatus(400);
                $result = [
                    'loggedin' => false,
                    'error' => $PMF_LANG['ad_auth_fail']
                ];
            }
        } else {
            $auth = false;
            $http->setStatus(400);
            $result = [
                'loggedin' => false,
                'error' => $PMF_LANG['ad_auth_fail']
            ];
        }
        break;

    case 'register':
        $http->setStatus(418);
        break;
}

//
// Check if FAQ should be secured
//
if (!$auth && $faqConfig->get('security.enableLoginOnly')) {
    $http->setStatus(403);
    $http->sendJsonWithHeaders([ 'error' => 'You are not allowed to view this content.' ]);
    exit();
}

$http->sendJsonWithHeaders($result);
