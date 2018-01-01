<?php

/**
 * PDF export.
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
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-12
 */
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'src/Bootstrap.php';

// get language (default: english)
$Language = new phpMyFAQ\Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

// Found an article language?
$lang = phpMyFAQ\Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_STRING);
if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
    $lang = phpMyFAQ\Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
    if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
        $lang = $LANGCODE;
    }
}

if (isset($lang) && Language::isASupportedLanguage($lang)) {
    require_once 'lang/language_'.$lang.'.php';
} else {
    $lang = 'en';
    require_once 'lang/language_en.php';
}
//
// Initializing static string wrapper
//
Strings::init($LANGCODE);

// authenticate with session information
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if ($user instanceof CurrentUser) {
    $auth = true;
} else {
    $user = null;
}

// Get current user and group id - default: -1
if (!is_null($user) && $user instanceof CurrentUser) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $current_user = -1;
    $current_groups = array(-1);
}

$currentCategory = phpMyFAQ\Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id = phpMyFAQ\Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$getAll = phpMyFAQ\Filter::filterInput(INPUT_GET, 'getAll', FILTER_VALIDATE_BOOLEAN, false);

$faq = new phpMyFAQ\Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

$category = new phpMyFAQ\Category($faqConfig, $current_groups, true);
$category->setUser($current_user);

$pdf = new phpMyFAQ\Export_Pdf($faq, $category, $faqConfig);
$http = new phpMyFAQ\Helper_Http();

if (true === $getAll) {
    $category->buildTree();
}
$tags = new phpMyFAQ\Tags($faqConfig);

session_cache_limiter('private');

$headers = array(
    'Pragma: public',
    'Expires: 0',
    'Cache-Control: must-revalidate, post-check=0, pre-check=0',
);

if (true === $getAll && $user->perm->checkRight($user->getUserId(), 'export')) {
    $filename = 'FAQs.pdf';
    $pdfFile = $pdf->generate(0, true, $lang);
} else {
    if (is_null($currentCategory) || is_null($id)) {
        $http->redirect($faqConfig->getDefaultUrl());
        exit();
    }

    $faq->getRecord($id);
    $faq->faqRecord['category_id'] = $currentCategory;

    $filename = 'FAQ-'.$id.'-'.$lang.'.pdf';
    $pdfFile = $pdf->generateFile($faq->faqRecord, $filename);
}

if (preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])) {
    $headers[] = 'Content-type: application/pdf';
    $headers[] = 'Content-Transfer-Encoding: binary';
    $headers[] = 'Content-Disposition: attachment; filename='.$filename;
} else {
    $headers[] = 'Content-Type: application/pdf';
}

$http->sendWithHeaders($pdfFile, $headers);
