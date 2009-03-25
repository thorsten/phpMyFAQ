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
 * @copyright  2003-2009 phpMyFAQ Team
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

require_once 'inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$category = new PMF_Category();

// get language (default: english)
$pmf      = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    require_once "lang/language_".$LANGCODE.".php";
} else {
    $LANGCODE = "en";
    require_once "lang/language_en.php";
}

$currentCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id              = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (is_null($currentCategory) || is_null($id)) {
    print "Error!";
    exit();
}

$faq = new PMF_Faq();
$faq->getRecord($id);

$pdf = new PMF_Export_Pdf($currentCategory, $faq->faqRecord['title'], $category->categoryName, $orientation = "P", $unit = "mm", $format = "A4");
$pdf->Open();
$pdf->SetTitle($faq->faqRecord['title']);
$pdf->SetCreator($faqconfig->get('main.titleFAQ')." - powered by phpMyFAQ ".$faqconfig->get('main.currentVersion'));
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont("Helvetica", "", 12);
$pdf->SetDisplayMode("real");
$pdf->WriteHTML(str_replace("../", '', $faq->faqRecord['content']));
$pdf->Ln();
$pdf->Ln();
$pdf->SetStyle('I', true);
$pdf->Write(5, html_entity_decode($PMF_LANG['ad_entry_solution_id']).': #'.$faq->faqRecord['solution_id']);
$pdf->SetAuthor($faq->faqRecord['author']);
$pdf->Ln();
$pdf->Write(5, html_entity_decode($PMF_LANG["msgAuthor"]).$faq->faqRecord['author']);
$pdf->Ln();
$pdf->Write(5, html_entity_decode($PMF_LANG["msgLastUpdateArticle"]).$faq->faqRecord['date']);
$pdf->SetStyle('I', false);

$pdfFile = "pdf/".$id.".pdf";
$pdf->Output($pdfFile);

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
    header("Content-Disposition: Attachment; filename=".$id.".pdf" );
    readfile($pdfFile);
} else {
    header("Location: ".$pdfFile."");
    header("Content-Type: application/pdf");
    header("Content-Length: ".filesize($pdfFile));
    readfile($pdfFile);
}
