<?php
/**
 * The REST Services interface
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_Service
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-03
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
 */

//
// Prepend and start the PHP session
//
require_once 'inc/Init.php';
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

// Send headers
header('Expires: Thu, 07 Apr 1977 14:47:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Vary: Negotiate,Accept');
header('Content-type: application/json');

// Set user permissions
$current_user   = -1;
$current_groups = array(-1);

$action   = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$language = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING, 'en');

// Get language (default: english)
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

// Set language
if (PMF_Language::isASupportedLanguage($language)) {
    $LANGCODE = trim($language);
    require_once 'lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
    require_once 'lang/language_en.php';
}

PMF_String::init('utf-8', $LANGCODE);

// Handle actions
switch ($action) {
    case 'search':
        $search       = new PMF_Search();
        $searchString = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
        $result       = $search->search($searchString, false, true, false);
        
        foreach ($result as &$data) {
        	$data->answer = strip_tags($data->answer);
        }
        
        print json_encode($result);
        break;
}