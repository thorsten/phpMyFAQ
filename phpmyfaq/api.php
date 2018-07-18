<?php

/**
 * The REST API.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-03
 */

define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Category;
use phpMyFAQ\Comment;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\News;
use phpMyFAQ\Search;
use phpMyFAQ\Search\Resultset;
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
$currentGroups = array(-1);
$auth = false;

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$language = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

//
// Set language
//
if (Language::isASupportedLanguage($language)) {
    require LANGUAGE_DIR.'/language_'.$language.'.php';
} else {
    require LANGUAGE_DIR.'/language_en.php';
}
$faqConfig->setLanguage($Language);

$plr = new Plurals($PMF_LANG);
Strings::init($language);

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

    case 'getVersion':
        $result = ['version' => $faqConfig->get('main.currentVersion')];
        break;

    case 'getApiVersion':
        $result = ['apiVersion' => $faqConfig->get('main.currentApiVersion')];
        break;

    case 'getCount':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = ['faqCount' => $faq->getNumberOfRecords($language)];
        break;

    case 'getDefaultLanguage':
        $result = ['defaultLanguage' => $faqConfig->getLanguage()->getLanguage()];
        break;

    case 'search':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $user = new CurrentUser($faqConfig);
        $search = new Search($faqConfig);
        $search->setCategory(new Category($faqConfig));

        $faqSearchResult = new Resultset($user, $faq, $faqConfig);
        $searchString = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        try {
            $searchResults = $search->search($searchString, false);
            $url = $faqConfig->getDefaultUrl().'index.php?action=faq&cat=%d&id=%d&artlang=%s';
            $faqSearchResult->reviewResultset($searchResults);
            foreach ($faqSearchResult->getResultset() as $data) {
                $data->answer = html_entity_decode(strip_tags($data->answer), ENT_COMPAT, 'utf-8');
                $data->answer = Utils::makeShorterText($data->answer, 12);
                $data->link = sprintf($url, $data->category_id, $data->id, $data->lang);
                $result[] = $data;
            }
        } catch (Search\Exception $e) {
            $result = [ 'error' => $e->getMessage() ];
        }
        break;

    case 'getCategories':
        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $result = array_values($category->getAllCategories());
        break;

    case 'getFaqs':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getAllRecordPerCategory($categoryId);
        break;

    case 'getFAQsByTag':
        $tags = new Tags($faqConfig);
        $recordIds = $tags->getRecordsByTagId($tagId);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getRecordsByIds($recordIds);
        break;

    case 'getFaq':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;

    case 'getComments':
        $comment = new Comment($faqConfig);
        $result = $comment->getCommentsData($recordId, 'faq');
        break;

    case 'getAllFaqs':
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $language]);
        $result = $faq->faqRecords;
        break;

    case 'getFaqAsPdf':
        $service = new Services($faqConfig);
        $service->setFaqId($recordId);
        $service->setLanguage($language);
        $service->setCategoryId($categoryId);

        $result = ['pdfUrl' => $service->getPdfApiLink()];
        break;

    case 'getAttachmentsFromFaq':
        $attachments = $result = [];
        try {
            $attachments = Factory::fetchByRecordId($faqConfig, $recordId);
        } catch (\phpMyFAQ\Attachment\Exception $e) {
            $result = [ 'error' => $e->getMessage() ];
        }
        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl().$attachment->buildUrl(),
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

    case 'getPopularSearches':
        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        break;

    case 'getPopularTags':
        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
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
                    'error' => $PMF_LANG['ad_auth_fail'].' ('.$faqusername.')'
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
