<?php
/**
 * PDF export
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-12
 */

use Symfony\Component\HttpFoundation\Response;

define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'inc/Bootstrap.php';

// get language (default: english)
$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

// Found an article language?
$lang = PMF_Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_STRING);
if (is_null($lang) && !PMF_Language::isASupportedLanguage($lang) ) {
    $lang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
    if (is_null($lang) && !PMF_Language::isASupportedLanguage($lang) ) {
        $lang = $LANGCODE;
    }
}

if (isset($lang) && PMF_Language::isASupportedLanguage($lang)) {
    require_once "lang/language_".$lang.".php";
} else {
    $lang = "en";
    require_once "lang/language_en.php";
}
//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

// authenticate with session information
$user = PMF_User_CurrentUser::getFromSession($faqConfig);
if ($user) {
    $auth = true;
} else {
    $user = null;
}

// Get current user rights
$permission = array();
if (isset($auth)) {
    // read all rights, set them FALSE
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }
    // check user rights, set them TRUE
    $allUserRights = $user->perm->getAllUserRights($user->getUserId());
    foreach ($allRights as $right) {
        if (in_array($right['right_id'], $allUserRights))
            $permission[$right['name']] = true;
    }
}

// Get current user and group id - default: -1
if (!is_null($user) && $user instanceof PMF_User_CurrentUser) {
    $current_user   = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $current_user   = -1;
    $current_groups = array(-1);
}

$currentCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id              = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$getAll          = PMF_Filter::filterInput(INPUT_GET, 'getAll', FILTER_VALIDATE_BOOLEAN, false);

$faq = new PMF_Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

$category = new PMF_Category($faqConfig, $current_groups, true);
$category->setUser($current_user);

$pdf  = new PMF_Export_Pdf($faq, $category, $faqConfig);

if (true === $getAll) {
    $category->buildTree();
}
$tags = new PMF_Tags($faqConfig);

session_cache_limiter('private');

if (true === $getAll && $permission['export']) {
    $filename = 'FAQs.pdf';
    $pdfFile  = $pdf->generate(0, true, $lang);
} elseif (is_null($currentCategory) || is_null($id)) {
    Response::create('Wrong HTTP GET parameters values.', 403)->send();
    exit;
} else {
    if (is_null($currentCategory) || is_null($id)) {
        $http->redirect($faqConfig->get('main.referenceURL'));
        exit();
    }

    $faq->getRecord($id);
    $faq->faqRecord['category_id'] = $currentCategory;

    $filename = 'FAQ-' . $id . '-' . $lang . '.pdf';
    $pdfFile  = $pdf->generateFile($faq->faqRecord, $filename);
}

$response = Response::create($pdfFile);
$response->headers->set('Pragma', 'public');
$response->headers->set('Expires', '0');
$response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
$response->headers->set('Content-type', 'application/pdf');

if (preg_match("/MSIE/i", $_SERVER["HTTP_USER_AGENT"])) {
	$response->headers->set('Content-Transfer-Encoding', 'binary');
	$response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
}

$response->send();
