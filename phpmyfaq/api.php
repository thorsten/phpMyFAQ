<?php

/**
 * The rest/json application interface.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-03
 */
<<<<<<< HEAD

use Symfony\Component\HttpFoundation\JsonResponse;

use PMF\Helper\ResponseWrapper;

=======
>>>>>>> 2.10
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'src/Bootstrap.php';

<<<<<<< HEAD
$response = new JsonResponse;

// Send headers
$responseWrapper = new ResponseWrapper($response);
$responseWrapper->addCommonHeaders();
=======
//
// Send headers
//
$http = new PMF_Helper_Http();
$http->setContentType('application/json');
$http->addHeader();
>>>>>>> 2.10

//
// Set user permissions
//
$currentUser = -1;
$currentGroups = array(-1);
$auth = false;

$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId = PMF_Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);
$tagId = PMF_Filter::filterInput(INPUT_GET, 'tagId', FILTER_VALIDATE_INT);

$faqusername = PMF_Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = PMF_Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);

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
// Check if user is already authenticated
//
if (is_null($faqusername) && is_null($faqpassword)) {

    $currentUser = PMF_User_CurrentUser::getFromCookie($faqConfig);
    // authenticate with session information
    if (!$currentUser instanceof PMF_User_CurrentUser) {
        $currentUser = PMF_User_CurrentUser::getFromSession($faqConfig);
    }
    if ($currentUser instanceof PMF_User_CurrentUser) {
        $auth = true;
    } else {
        $currentUser = new PMF_User_CurrentUser($faqConfig);
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
        $url = $faqConfig->getDefaultUrl().'index.php?action=faq&cat=%d&id=%d&artlang=%s';

        $faqSearchResult->reviewResultset($searchResults);
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

    case 'getComments':
        $comment = new PMF_Comment($faqConfig);

        $result = $comment->getCommentsData($recordId, 'faq');
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
        $result = array_values($faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN));
        break;

    case 'getLatest':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = array_values($faq->getLatestData(PMF_NUMBER_RECORDS_LATEST));
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

    case 'login':
        $currentUser = new PMF_User_CurrentUser($faqConfig);
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
    echo json_encode(
        [
            'error' => 'You are not allowed to view this content.'
        ]
    );
    $http->sendStatus(403);
}

// print result as JSON
<<<<<<< HEAD
$response->setData($result)->send();
=======
echo json_encode($result);
>>>>>>> 2.10
