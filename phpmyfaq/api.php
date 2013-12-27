<?php
/**
 * The rest/json application interface
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_Service
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-03
 */

define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'inc/Bootstrap.php';

// Send headers
$http = new PMF_Helper_Http();
$http->setContentType('application/json');
$http->addHeader();

// Set user permissions
$currentUser   = -1;
$currentGroups = array(-1);

$action     = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language   = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING, 'en');
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'categoryId', FILTER_VALIDATE_INT);
$recordId   = PMF_Filter::filterInput(INPUT_GET, 'recordId', FILTER_VALIDATE_INT);

// Get language (default: english)
$Language = new PMF_Language($faqConfig);
$language = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

// Set language
if (PMF_Language::isASupportedLanguage($language)) {
    require PMF_LANGUAGE_DIR . '/language_' . $language . '.php';
} else {
    require PMF_LANGUAGE_DIR . '/language_en.php';
}
$faqConfig->setLanguage($Language);

$plr = new PMF_Language_Plurals($PMF_LANG);
PMF_String::init($language);

// Set empty result
$result = [];

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
        $category = new PMF_Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $result   = $category->categories;
        break;
        
    case 'getFaqs':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $result = $faq->getAllRecordPerCategory($categoryId);
        break;
        
    case 'getFaq':
        $faq = new PMF_Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;

    default:
        $result = 'I am completely operational, and all my circuits are functioning perfectly.';
        break;
}

// print result as JSON
echo json_encode($result);