<?php
/**
 * The rest/json application interface
 *
 * PHP Version 5.3.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_Service
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-03
 */

//
// Prepend and start the PHP session
//
define('IS_VALID_PHPMYFAQ', null);
require 'inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_Session::PMF_COOKIE_NAME_AUTH);
session_start();

// Send headers
$http = PMF_Helper_Http::getInstance();
$http->setContentType('application/json');
$http->addHeader();

// Set user permissions
$current_user   = -1;
$current_groups = array(-1);

$action     = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language   = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId   = PMF_Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);

// Get language (default: english)
$Language = new PMF_Language();
$language = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

// Set language
if (PMF_Language::isASupportedLanguage($language)) {
    require 'lang/language_' . $language . '.php';
} else {
    require 'lang/language_en.php';
}

$plr = new PMF_Language_Plurals($PMF_LANG);
PMF_String::init($language);

// Set empty result
$result = array();

// Handle actions
switch ($action) {
    case 'getVersion':
        $result = array('version' => $faqConfig->get('main.currentVersion'));
        break;
        
    case 'getApiVersion':
        $result = array('apiVersion' => (int)$faqConfig->get('main.currentApiVersion'));
        break;
        
    case 'search':
        $search       = new PMF_Search($faqConfig);
        $searchString = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        $result       = $search->search($searchString, false);
        $url          = $faqConfig->get('main.referenceURL') . '/index.php?action=artikel&cat=%d&id=%d&artlang=%s';
        
        foreach ($result as &$data) {
            $data->answer = html_entity_decode(strip_tags($data->answer), ENT_COMPAT, 'utf-8');
            $data->answer = PMF_Utils::makeShorterText($data->answer, 12);
            $data->link   = sprintf($url, $data->category_id, $data->id, $data->lang);
        }
        break;
        
    case 'getCategories':
        $category = new PMF_Category($faqConfig, true);
        $category->setUser($current_user);
        $category->setGroups($current_user);
        $result   = $category->categories;
        break;
        
    case 'getFaqs':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($current_user);
        $faq->setGroups($current_user);
        $result = $faq->getAllRecordPerCategory($categoryId);
        break;
        
    case 'getFaq':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($current_user);
        $faq->setGroups($current_user);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;
}

// print result as JSON
print json_encode($result);