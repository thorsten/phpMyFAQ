<?php
/**
 * $Id$
 *
 * The Ajax driven response page
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2007-03-27
 * @copyright   (c) 2007 phpMyFAQ Team
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
require_once('inc/Init.php');
require_once('inc/Category.php');
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name('pmf_auth_'.$faqconfig->get('main.phpMyFAQToken'));
session_start();

$searchString = '';

if (isset($_POST['ajaxlanguage']) && PMF_Init::isASupportedLanguage($_POST['ajaxlanguage'])) {
    $LANGCODE = trim($_POST['ajaxlanguage']);
    require_once('lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
    require_once('lang/language_en.php');
}

$category = new PMF_Category($LANGCODE);
$category->transform(0);
$category->buildTree();

//
// Handle the search requests
//
if (isset($_POST['search'])) {
    $searchString = $db->escape_string(trim(strip_tags($_POST['search'])));
    $result = searchEngine($searchString, '%', false, true, true);
    if ($PMF_LANG['metaCharset'] != 'utf-8') {
        print utf8_encode($result);
    } else {
        print $result;
    }
}
