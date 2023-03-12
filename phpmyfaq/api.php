<?php

/**
 * The REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-03
 */

const IS_VALID_PHPMYFAQ = null;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\CategoryEntity;
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
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserAuthentication;
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

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

$faqUsername = Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
$faqPassword = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);

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

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($currentLanguage);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

// Load plurals support for selected language
$plr = new Plurals();
Strings::init($currentLanguage);

//
// Set empty result
$result = [];

//
// Check if user is already authenticated
//
[ $user, $auth ] = CurrentUser::getCurrentUser($faqConfig);
[ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

//
// Handle actions
//
switch ($action) {
    //
    // v2.2
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

        $searchString = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
        try {
            $searchResults = $search->search($searchString, false);
            $faqSearchResult->reviewResultSet($searchResults);
            if ($faqSearchResult->getNumberOfResults() > 0) {
                $url = $faqConfig->getDefaultUrl() . 'index.php?action=faq&cat=%d&id=%d&artlang=%s';
                foreach ($faqSearchResult->getResultSet() as $data) {
                    $data->answer = html_entity_decode(strip_tags((string) $data->answer), ENT_COMPAT, 'utf-8');
                    $data->answer = Utils::makeShorterText($data->answer, 12);
                    $data->link = sprintf($url, $data->category_id, $data->id, $data->lang);
                    $result[] = $data;
                }
            } else {
                $http->setStatus(404);
            }
        } catch (Exception) {
            $http->setStatus(400);
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

    case 'category':
        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $categoryPermission = new CategoryPermission($faqConfig);

        //
        // POST
        //
        if ($faqConfig->get('api.apiClientToken') !== $http->getClientApiToken()) {
            $http->setStatus(401);
            $result = [
                'stored' => false,
                'error' => 'X_PMF_Token not valid.'
            ];
            break;
        }

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $languageCode = Filter::filterVar($postData['language'], FILTER_SANITIZE_SPECIAL_CHARS);
        $parentId = Filter::filterVar($postData['parent-id'], FILTER_VALIDATE_INT);

        if (isset($postData['parent-category-name'])) {
            $parentCategoryName = Filter::filterVar($postData['parent-category-name'], FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $parentCategoryName = null;
        }

        $name = Filter::filterVar($postData['category-name'], FILTER_SANITIZE_SPECIAL_CHARS);
        $description = Filter::filterVar($postData['description'], FILTER_SANITIZE_SPECIAL_CHARS);

        if (isset($postData['user-id'])) {
            $userId = Filter::filterVar($postData['user-id'], FILTER_VALIDATE_INT);
        } else {
            $userId = 1;
        }

        if (isset($postData['group-id'])) {
            $groupId = Filter::filterVar($postData['group-id'], FILTER_VALIDATE_INT);
        } else {
            $groupId = -1;
        }

        $active = Filter::filterVar($postData['is-active'], FILTER_VALIDATE_BOOLEAN);
        $showOnHome = Filter::filterVar($postData['show-on-homepage'], FILTER_VALIDATE_BOOLEAN);

        // Check if parent category name can be mapped
        if (!is_null($parentCategoryName)) {
            $parentCategoryIdFound = $category->getCategoryIdFromName($parentCategoryName);
            if ($parentCategoryIdFound === false) {
                $http->setStatus(409);
                $result = [
                    'stored' => false,
                    'error' => 'The given parent category name was not found.'
                ];
                break;
            }

            $parentId = $parentCategoryIdFound;
        }

        $categoryData = new CategoryEntity();
        $categoryData
            ->setLang($languageCode)
            ->setParentId($parentId)
            ->setName($name)
            ->setDescription($description)
            ->setUserId($userId)
            ->setGroupId($groupId)
            ->setActive($active)
            ->setImage('')
            ->setShowHome($showOnHome);

        $categoryId = $category->create($categoryData);

        if ($categoryId) {
            $categoryPermission->add(CategoryPermission::USER, [$categoryId], [-1]);
            $categoryPermission->add(CategoryPermission::GROUP, [$categoryId], [-1]);

            $http->setStatus(200);
            $result = [
                'stored' => true
            ];
        } else {
            $http->setStatus(400);
            $result = [
                'stored' => false,
                'error' => 'Cannot add category'
            ];
        }
        break;

    case 'groups':
        $groupPermission = new MediumPermission($faqConfig);
        $result = $groupPermission->getAllGroups($user);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'tags':
        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'open-questions':
        $questions = new Question($faqConfig);
        $result = $questions->getAllOpenQuestions();
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'searches':
        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'comments':
        $comment = new Comments($faqConfig);
        $result = $comment->getCommentsData($recordId, CommentType::FAQ);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;

    case 'attachments':
        $attachments = $result = [];
        try {
            $attachments = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        } catch (AttachmentException) {
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
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $http->setStatus(404);
        }
        break;


    case 'faqs':
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        // api/v2.2/faqs/:categoryId
        if (!is_null($categoryId)) {
            try {
                if ('all' === $filter) {
                    $result = $faq->getAllFaqsByCategoryId($categoryId, 'id', 'ASC', false);
                } else {
                    $result = $faq->getAllFaqsByCategoryId($categoryId);
                }
            } catch (Exception) {
                $http->setStatus(400);
            }
        }

        // api/v2.2/faqs/tags/:tagId
        if (!is_null($tagId)) {
            $tags = new Tags($faqConfig);
            $recordIds = $tags->getFaqsByTagId($tagId);
            try {
                $result = $faq->getRecordsByIds($recordIds);
            } catch (Exception) {
                $http->setStatus(400);
            }
        }

        // api/v2.2/faqs/popular
        if ('popular' === $filter) {
            $result = array_values($faq->getTopTenData());
        }

        // api/v2.2/faqs/latest
        if ('latest' === $filter) {
            $result = array_values($faq->getLatestData());
        }

        // api/v2.2/faqs/sticky
        if ('sticky' === $filter) {
            $result = array_values($faq->getStickyRecordsData());
        }

        // api/v2.2/faqs
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
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
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
            break;
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
            break;
        }

        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $languageCode = Filter::filterVar($postData['language'], FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterVar($postData['category-id'], FILTER_VALIDATE_INT);
        if (isset($postData['category-name'])) {
            $categoryName = Filter::filterVar($postData['category-name'], FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $categoryName = null;
        }
        $question = Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($postData['answer'], FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($postData['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($postData['author'], FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($postData['email'], FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($postData['is-active'], FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($postData['is-sticky'], FILTER_VALIDATE_BOOLEAN);

        // Check if category name can be mapped
        if (!is_null($categoryName)) {
            $categoryIdFound = $category->getCategoryIdFromName($categoryName);
            if ($categoryIdFound === false) {
                $http->setStatus(409);
                $result = [
                    'stored' => false,
                    'error' => 'The given category name was not found.'
                ];
                break;
            }

            $categoryId = $categoryIdFound;
        }

        $categories = [ $categoryId ];
        $isActive = !is_null($isActive);
        $isSticky = !is_null($isSticky);

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
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $faqUsername = Filter::filterVar($postData['username'], FILTER_SANITIZE_SPECIAL_CHARS);
        $faqPassword = Filter::filterVar($postData['password'], FILTER_SANITIZE_SPECIAL_CHARS);

        $user = new CurrentUser($faqConfig);
        $userAuth = new UserAuthentication($faqConfig, $user);
        try {
            [ $user, $auth ] = $userAuth->authenticate($faqUsername, $faqPassword);
            $http->setStatus(200);
            $result = [
                'loggedin' => true
            ];
        } catch (Exception $e) {
            $faqConfig->getLogger()->error('Failed login: ' . $e->getMessage());
            $http->setStatus(400);
            $result = [
                'loggedin' => false,
                'error' => Translation::get('ad_auth_fail')
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

        $userName = Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $fullName = Filter::filterInput(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $isVisible = Filter::filterInput(INPUT_POST, 'is-visible', FILTER_SANITIZE_SPECIAL_CHARS);
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
                'error' => Translation::get('err_sendMail')
            ];
        }

        break;

    case 'question':
        if ($faqConfig->get('api.apiClientToken') !== $http->getClientApiToken()) {
            $http->setStatus(401);
            $result = [
                'stored' => false,
                'error' => 'X_PMF_Token not valid.'
            ];
            break;
        }

        $languageCode = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterInput(INPUT_POST, 'category-id', FILTER_VALIDATE_INT);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($faqConfig->get('records.enableVisibilityQuestions')) {
            $visibility = 'Y';
        } else {
            $visibility = 'N';
        }

        $questionData = [
            'username' => $author,
            'email' => $email,
            'category_id' => $categoryId,
            'question' => $question,
            'is_visible' => $visibility
        ];

        $questionObject = new Question($this->config);
        $questionObject->addQuestion($questionData);

        $result = [
            'stored' => true
        ];
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
