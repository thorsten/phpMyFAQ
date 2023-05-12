<?php

/**
 * PDF export.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-12
 */

use phpMyFAQ\Category;
use phpMyFAQ\Export\Pdf;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require 'src/Bootstrap.php';

// get language (default: english)
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

// Found an article language?
$lang = Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
    $lang = Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_SPECIAL_CHARS);
    if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
        $lang = $faqLangCode;
    }
}

if (isset($lang) && Language::isASupportedLanguage($lang)) {
    require_once 'lang/language_' . $lang . '.php';
} else {
    $lang = 'en';
    require_once 'lang/language_en.php';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

// authenticate with session information
$user = CurrentUser::getCurrentUser($faqConfig);

// Get current user and group id - default: -1
[ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

$request = Request::createFromGlobals();
$currentCategory = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT);
$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT);
$getAll = Filter::filterVar($request->query->get('getAll'), FILTER_VALIDATE_BOOLEAN, false);

$faq = new Faq($faqConfig);
$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

$category = new Category($faqConfig, $currentGroups, true);
$category->setUser($currentUser);

try {
    $pdf = new Pdf($faq, $category, $faqConfig);
} catch (Exception) {
    // handle exception
}

$response = new Response();

if (true === $getAll) {
    $category->buildCategoryTree();
}

$tags = new Tags($faqConfig);

$response->setExpires(new DateTime());

if (true === $getAll && $user->perm->hasPermission($user->getUserId(), 'export')) {
    $filename = 'FAQs.pdf';
    $pdfFile = $pdf->generate(0, true, $lang);
} else {
    if (is_null($currentCategory) || is_null($id)) {
        $response->isRedirect($faqConfig->getDefaultUrl());
        $response->send();
        exit();
    }

    $faq->getRecord($id);
    $faq->faqRecord['category_id'] = $currentCategory;

    $filename = 'FAQ-' . $id . '-' . $lang . '.pdf';
    $pdfFile = $pdf->generateFile($faq->faqRecord, $filename);
}

$response->headers->set('Content-Type', 'application/pdf');
$response->setContent($pdfFile);
$response->send();
