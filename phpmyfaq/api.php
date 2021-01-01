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
 * @copyright 2009-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-09-03
 */

define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\RegistrationHelper;
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
$http->fetchAllHeaders();
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
$faqPassword = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

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
    // v2.1
    //
    case 'version':
        $result = $faqConfig->getVersion();
        break;

    case 'language':
        $result = $faqConfig->getLanguage()->getLanguage();
        break;

    case 'search':
        $user = new CurrentUser($faqConfig);
        $search = new Search($faqConfig);
        $search->setCategory(new Category($faqConfig));

        $faqPermission = new FaqPermission($faqConfig);
        $faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

        $searchString = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        $searchResults = $search->search($searchString, false);
        $faqSearchResult->reviewResultSet($searchResults);
        if ($faqSearchResult->getNumberOfResults() > 0) {
            $url = $faqConfig->getDefaultUrl() . 'index.php?action=faq&cat=%d&id=%d&artlang=%s';
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

        // api/v2.1/faqs/:categoryId
        if (!is_null($categoryId)) {
            try {
                $result = $faq->getAllRecordsByCategoryId($categoryId);
            } catch (Exception $e) {
                $http->setStatus(400);
            }
        }

        // api/v2.1/faqs/tags/:tagId
        if (!is_null($tagId)) {
            $tags = new Tags($faqConfig);
            $recordIds = $tags->getFaqsByTagId($tagId);
            try {
                $result = $faq->getRecordsByIds($recordIds);
            } catch (Exception $e) {
                $http->setStatus(400);
            }
        }

        // api/v2.1/faqs/popular
        if ('popular' === $filter) {
            $result = array_values($faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN));
        }

        // api/v2.1/faqs/latest
        if ('latest' === $filter) {
            $result = array_values($faq->getLatestData(PMF_NUMBER_RECORDS_LATEST));
        }

        // api/v2.1/faqs/sticky
        if ('sticky' === $filter) {
            $result = array_values($faq->getStickyRecordsData());
        }

        // api/v2.1/faqs
        if (is_null($categoryId) && is_null($tagId) && is_null($filter)) {
            $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $currentLanguage]);
            $result = $faq->faqRecords;
        }

        if (count($result) === 0) {
            $http->setStatus(404);
        }
        break;


    case 'faq':
        //
        // GET
        //
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        if ($recordId > 0) {
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
        }

        //
        // POST
        //
        if ($faqConfig->get('api.apiClientToken') !== $http->getClientApiToken()) {
            $http->setStatus(401);
            $result = [
                'stored' => false,
                'error' => 'X_PMF_Token not valid.'
            ];
        }

        $languageCode = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
        $categoryId = Filter::filterInput(INPUT_POST, 'category-id', FILTER_VALIDATE_INT);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
        $answer = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_STRING);
        $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
        $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
        $isActive = Filter::filterInput(INPUT_POST, 'is-active', FILTER_SANITIZE_STRING);
        $isSticky = Filter::filterInput(INPUT_POST, 'is-sticky', FILTER_SANITIZE_STRING);

        $categories = [ $categoryId ];
        $isActive = $isActive === 'true';
        $isSticky = $isSticky === 'true';

        $faqData = new FaqEntity();
        $faqData
            ->setLanguage($languageCode)
            ->setQuestion($question)
            ->setAnswer($answer)
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($isActive)
            ->setSticky($isSticky)
            ->setComment(false)
            ->setLinkState('')
            ->setNotes('');

        $faqId = $faq->create($faqData);

        $faqMetaData = new FaqMetaData($faqConfig);
        $faqMetaData
            ->setFaqId($faqId)
            ->setFaqLanguage($languageCode)
            ->setCategories($categories)
            ->save();

        $result = [
            'stored' => true
        ];
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
        if ($faqConfig->get('api.apiClientToken') !== $http->getClientApiToken()) {
            $http->setStatus(401);
            $result = [
                'registered' => false,
                'error' => 'X_PMF_Token not valid.'
            ];
            break;
        }

        $registration = new RegistrationHelper($faqConfig);

        $userName = Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $fullName = Filter::filterInput(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $isVisible = Filter::filterInput(INPUT_POST, 'is-visible', FILTER_SANITIZE_STRING);
        $isVisible = $isVisible === 'true';

        if (!$registration->isDomainWhitelisted($email)) {
            $http->setStatus(400);
            $result = [
                'registered' => false,
                'error' => 'The domain is not whitelisted.'
            ];
            break;
        }

        if (!is_null($userName) && !is_null($fullName) && !is_null($email)) {
            $result = $registration->createUser($userName, $fullName, $email, $isVisible);
            $http->setStatus(200);
        } else {
            $http->setStatus(400);
            $result = [
                'registered' => false,
                'error' => $PMF_LANG['err_sendMail']
            ];
        }

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
