<?php
/**
 * PDF export
 * 
 * PHP Version 5.2
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
 * @package   Frontend 
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-12 
 */

define('IS_VALID_PHPMYFAQ', null);

require_once 'inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

// get language (default: english)
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

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
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));
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
    if ($user->perm instanceof PMF_Perm_PermMedium) {
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

$faq = new PMF_Faq($current_user, $current_groups);
$faq->setLanguage($lang);

$category = new PMF_Category($current_user, $current_groups);
$pdf      = new PMF_Export_Pdf($faq, $category);

session_cache_limiter('private');
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

if (true === $getAll) {

    $filename = 'FAQs.pdf';
    $pdfFile  = $pdf->generate(0, true, $lang);

} else {

    if (is_null($currentCategory) || is_null($id)) {
        header('HTTP/1.1 403 Forbidden');
        print 'Wrong HTTP GET parameters values.';
        exit();
    }

    $faq->getRecord($id);
    $faq->faqRecord['category_id'] = $currentCategory;

    $filename = 'FAQ-' . $id . '-' . $lang . '.pdf';
    $pdfFile  = $pdf->generateFile($faq->faqRecord, $filename);
}

if (preg_match("/MSIE/i", $_SERVER["HTTP_USER_AGENT"])) {
    header("Content-type: application/pdf");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: attachment; filename=" . $filename);
} else {
    header("Content-Type: application/pdf");
}

print $pdfFile;