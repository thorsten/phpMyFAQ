<?php
/**
 * The RSS feed with the latest open questions.
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
 * @package   PMF_Feed
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-17
 */

define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));
define('IS_VALID_PHPMYFAQ', null);

require_once(PMF_ROOT_DIR.'/inc/Init.php');
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

$faq     = new PMF_Faq();
$rssData = $faq->getAllOpenQuestions(false);
$num     = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', $faqconfig->get('main.titleFAQ') . ' - ' . $PMF_LANG['msgOpenQuestions']);
$rss->writeElement('description', html_entity_decode($faqconfig->get('main.metaDescription')));
$rss->writeElement('link', PMF_Link::getSystemUri('/feed/openquestions/rss.php'));

if ($num > 0) {
    $counter = 0;
    foreach ($rssData as $item) {
        if ($counter < PMF_RSS_OPENQUESTIONS_MAX) {
            $counter++;

            $rss->startElement('item');
            $rss->writeElement('title', PMF_Utils::makeShorterText(html_entity_decode($item['question'], ENT_COMPAT, 'UTF-8'), 8) .
                                        " (".$item['user'].")");
            
            $rss->startElement('description');
            $rss->writeCdata($item['question']);
            $rss->endElement();
        
            $rss->writeElement('link', (isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER['SCRIPT_NAME'])."?action=open#openq_".$item['id']);
            $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['date'], true));
            $rss->endElement();
        }
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

header('Content-Type: application/rss+xml');
header('Content-Length: '.strlen($rssData));

print $rssData;

$db->dbclose();
