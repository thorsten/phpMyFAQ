<?php

/**
 * The rest/json application interface.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-03
 */
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'inc/Bootstrap.php';

//
// Send headers
//
$http = new PMF_Helper_Http();
$http->setContentType('application/json');
$http->addHeader();

//
// Set user permissions
//
$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}

if ($user instanceof PMF_User_CurrentUser) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = array(-1);
    }
    if (0 === count($currentGroups)) {
        $currentGroups = array(-1);
    }
}

$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = PMF_Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = PMF_Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

//
// Get language (default: english)
//
$Language = new PMF_Language($faqConfig);
$language = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

//
// Set language
//
if (PMF_Language::isASupportedLanguage($language)) {
    require PMF_LANGUAGE_DIR.'/language_'.$language.'.php';
} else {
    require PMF_LANGUAGE_DIR.'/language_en.php';
}
$faqConfig->setLanguage($Language);

$plr = new PMF_Language_Plurals($PMF_LANG);
PMF_String::init($language);

//
// Set empty result
$result = [];

//
// Check if FAQ should be secured
//
if ($faqConfig->get('security.enableLoginOnly')) {
    echo json_encode(array('You are not allowed to view this content.'));
    $http->sendStatus(403);
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
        $faq = new PMF_Faq($faqConfig);
        $result = ['faqCount' => $faq->getNumberOfRecords($language)];
        break;

    case 'getDefaultLanguage':
        $result = ['defaultLanguage' => $faqConfig->getLanguage()->getLanguage()];
        break;

    case 'search':
        $faq = new PMF_Faq($faqConfig);
        $user = new PMF_User($faqConfig);
        $search = new PMF_Search($faqConfig);
        $search->setCategory(new PMF_Category($faqConfig));

        $faqSearchResult = new PMF_Search_Resultset($user, $faq, $faqConfig);

        $searchString = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        $searchResults = $search->search($searchString, false);
        $url = $faqConfig->getDefaultUrl().'index.php?action=artikel&cat=%d&id=%d&artlang=%s';

        $faqSearchResult->reviewResultset($searchResults);

        $result = [];
        foreach ($faqSearchResult->getResultset() as $data) {
            $data->answer = html_entity_decode(strip_tags($data->answer), ENT_COMPAT, 'utf-8');
            $data->answer = PMF_Utils::makeShorterText($data->answer, 12);
            $data->link = sprintf($url, $data->category_id, $data->id, $data->lang);
            $result[] = $data;
        }
        break;

    case 'getCategories':
        $category = new PMF_Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $result = array_values($category->getAllCategories());
        break;

    case 'getFaqs':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getAllRecordPerCategory($categoryId);
        break;

    case 'getFAQsByTag':
        $tags = new PMF_Tags($faqConfig);
        $recordIds = $tags->getRecordsByTagId($tagId);
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getRecordsByIds($recordIds);
        break;

    case 'getFaq':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;

    case 'getAllFaqs':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $language]);
        $result = $faq->faqRecords;
        break;

    case 'getFaqAsPdf':
        $service = new PMF_Services($faqConfig);
        $service->setFaqId($recordId);
        $service->setLanguage($language);
        $service->setCategoryId($categoryId);

        $result = ['pdfUrl' => $service->getPdfApiLink()];
        break;

    case 'getAttachmentsFromFaq':
        $attachments = PMF_Attachment_Factory::fetchByRecordId($faqConfig, $recordId);
        $result = [];
        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl().$attachment->buildUrl(),
            ];
        }
        break;

    case 'getPopular':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN);
        break;

    case 'getLatest':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getLatestData(PMF_NUMBER_RECORDS_LATEST);
        break;

    case 'getNews':
        $news = new PMF_News($faqConfig);
        $result = $news->getLatestData(false, true, true);
        break;

    case 'getPopularSearches':
        $search = new PMF_Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        break;

    case 'getPopularTags':
        $tags = new PMF_Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        break;
}

// print result as JSON
echo json_encode($result);
