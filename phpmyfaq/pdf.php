<?php
/**
 * PDF export
 *
 * @package    phpMyFAQ
 * @subpackage Frontend 
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Peter Beauvain <pbeauvain@web.de>
 * @author     Olivier Plathey <olivier@fpdf.org>
 * @author     Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2003-02-12
 * @version    SVN: $Id$ 
 * @copyright  2003-2009 phpMyFAQ Team
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

require_once 'inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

// get language (default: english)
$pmf      = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    require_once "lang/language_".$LANGCODE.".php";
} else {
    $LANGCODE = "en";
    require_once "lang/language_en.php";
}
//
// Initalizing static string wrapper
//
PMF_String::init($PMF_LANG["metaCharset"], $LANGCODE);

$category = new PMF_Category();

$currentCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id              = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (is_null($currentCategory) || is_null($id)) {
    header('HTTP/1.1 403 Forbidden');
    print 'Wrong HTTP GET parameters values.';
    exit();
}

$faq = new PMF_Faq();
$faq->getRecord($id);
$pdfFile = $faq->buildPDFFile($currentCategory);

// Sanity check: stop here if no PDF has been created
if (empty($pdfFile) || (!file_exists($pdfFile))) {
    header('HTTP/1.1 404 Not Found');
    print 'PDF not available.';
    exit();
}

$file = basename($pdfFile);
$size = filesize($pdfFile);

session_cache_limiter('private');
header("Pragma: public");
header("Expires: 0"); // set expiration time
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

if (preg_match("/MSIE/i", $_SERVER["HTTP_USER_AGENT"])) {
    header("Content-type: application/pdf");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($pdfFile));
    header("Content-Disposition: attachment; filename=".$id.".pdf" );
    readfile($pdfFile);
} else {
    header("Location: ".$pdfFile."");
    header("Content-Type: application/pdf");
    header("Content-Length: ".filesize($pdfFile));
    readfile($pdfFile);
}
