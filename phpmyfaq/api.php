<?php
/**
 * The rest/json application interface
 * 
 * PHP Version 5.2.0
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ 
 * @package   PMF_Service
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-03
 */

//
// Prepend and start the PHP session
//
define('IS_VALID_PHPMYFAQ', null);
require 'inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqConfig->get('main.phpMyFAQToken')));
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
        $search       = new PMF_Search($db, $Language);
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
        $category = new PMF_Category($current_user, $current_groups, true);
        $result   = $category->categories;
        break;
        
    case 'getFaqs':
        $faq    = new PMF_Faq($current_user, $current_groups);
        $result = $faq->getAllRecordPerCategory($categoryId);
        break;
        
    case 'getFaq':
        $faq = new PMF_Faq($current_user, $current_groups);
        $faq->getRecord($recordId);
        $result = $faq->faqRecord;
        break;
}

// print result as JSON
print json_encode($result);