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
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\News;
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
$currentUser = -1;
$currentGroups = [-1];
$auth = false;

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);

//
// Get language (default: english)
//
$language = new Language($faqConfig);
$currentLanguage = $language->setLanguageByAcceptLanguage();

//
// Set language
//
if (Language::isASupportedLanguage($currentLanguage)) {
    require LANGUAGE_DIR . '/language_' . $currentLanguage . '.php';
} else {
    require LANGUAGE_DIR . '/language_en.php';
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
if (is_null($faqusername) && is_null($faqpassword)) {

    $currentUser = CurrentUser::getFromCookie($faqConfig);
    // authenticate with session information
    if (!$currentUser instanceof CurrentUser) {
        $currentUser = CurrentUser::getFromSession($faqConfig);
    }
    if ($currentUser instanceof CurrentUser) {
        $auth = true;
    } else {
        $currentUser = new CurrentUser($faqConfig);
    }
}

//
// Handle actions
//
switch ($action) {

    //
    // v1
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
            $http->sendStatus(404);
        }

        break;

    case 'categories':
        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);
        $result = array_values($category->getAllCategories());
        if (count($result) === 0) {
            $http->sendStatus(404);
        }
        break;

    case 'tags':
        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        if (count($result) === 0) {
            $http->sendStatus(404);
        }
        break;

    case 'open-questions':
        $questions = new Question($faqConfig);
        $result = $questions->getAllOpenQuestions();
        if (count($result) === 0) {
            $http->sendStatus(404);
        }
        break;

    case 'searches':
        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        if (count($result) === 0) {
            $http->sendStatus(404);
        }
        break;



    case 'getFaqs':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        try {
            $result = $faq->getAllRecordPerCategory($categoryId);
        } catch (Exception $e) {
            // @todo Handle exception
        }
        break;

    case 'getFAQsByTag':
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

    case 'getFaq':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;

    case 'getComments':
        $comment = new Comments($faqConfig);
        $result = $comment->getCommentsData($recordId, 'faq');
        break;

    case 'getAllFaqs':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $currentLanguage]);
        $result = $faq->faqRecords;
        break;

    case 'getFaqAsPdf':
        $service = new Services($faqConfig);
        $service->setFaqId($recordId);
        $service->setLanguage($currentLanguage);
        $service->setCategoryId($categoryId);

        $result = ['pdfUrl' => $service->getPdfApiLink()];
        break;

    case 'getAttachmentsFromFaq':
        $attachments = $result = [];
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

    case 'getPopular':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = array_values($faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN));
        break;

    case 'getLatest':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = array_values($faq->getLatestData(PMF_NUMBER_RECORDS_LATEST));
        break;

    case 'getNews':
        $news = new News($faqConfig);
        $result = $news->getLatestData(false, true, true);
        break;

    case 'login':
        $currentUser = new CurrentUser($faqConfig);
        if ($currentUser->login($faqusername, $faqpassword)) {
            if ($currentUser->getStatus() != 'blocked') {
                $auth = true;
                $result = [
                    'loggedin' => true
                ];
            } else {
                $result = [
                    'loggedin' => false,
                    'error' => $PMF_LANG['ad_auth_fail'] . ' (' . $faqusername . ')'
                ];
            }
        } else {
            $result = [
                'loggedin' => false,
                'error' => $PMF_LANG['ad_auth_fail']
            ];
        }
        break;
}

//
// Check if FAQ should be secured
//
if (!$auth && $faqConfig->get('security.enableLoginOnly')) {
    $http->sendJsonWithHeaders(
        [
            'error' => 'You are not allowed to view this content.'
        ]
    );
    $http->sendStatus(403);
}

$http->sendJsonWithHeaders($result);
