<?php
/**
 * The RSS feed with the latest open questions.
 *
 * @package     phpMyFAQ
 * @access      public
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @version     SVN: $Id$ 
 * @copyright   (c) 2006-2009 phpMyFAQ Team
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

define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));

require_once(PMF_ROOT_DIR.'/inc/Init.php');
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$pmf      = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$faq     = new PMF_Faq();
$rssData = $faq->getAllOpenQuestions(false);
$num     = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();

$rss->startDocument('1.0', $PMF_LANG['metaCharset']);
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', utf8_encode($PMF_CONF['main.titleFAQ']) . ' - ' . utf8_encode($PMF_LANG['msgOpenQuestions']));
$rss->writeElement('description', utf8_encode($PMF_CONF['main.metaDescription']));
$rss->writeElement('link', PMF_Link::getSystemUri('/feed/openquestions/rss.php'));

if ($num > 0) {
    $counter = 0;
    foreach ($rssData as $item) {
        if ($counter < PMF_RSS_OPENQUESTIONS_MAX) {
            $counter++;

            $rss->startElement('item');
            $rss->writeElement('title', utf8_encode(PMF_Utils::makeShorterText($item['question'], 8)." (".$item['user'].")"));
            
            $rss->startElement('description');
            $rss->writeCdata(utf8_encode($item['question']));
            $rss->endElement();
        
            $rss->writeElement('link', utf8_encode((isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER["PHP_SELF"])."?action=open#openq_".$item['id']));
            $rss->writeElement('pubDate', makeRFC822Date($item['date'], false));
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
