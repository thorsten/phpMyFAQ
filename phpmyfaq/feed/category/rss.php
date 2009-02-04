<?php
/**
 * The RSS feed for categories.
 *
 * @package     phpMyFAQ
 * @access      public
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2008-01-25 
 * @version     SVN: $Id$
 * @copyright   (c) 2008-2009 phpMyFAQ Team
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

require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$pmf      = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once PMF_ROOT_DIR.'/lang/language_en.php';

$category_id = $category_lang = null;
if (isset($_GET['category_id']) && is_numeric($_GET['category_id']) && ($_GET['category_id'] != 0)) {
    $category_id = (int)$_GET['category_id'];
}
if (isset($_GET['category_lang']) && PMF_Init::isASupportedLanguage($_GET['category_lang'])) {
    $category_lang = $_GET['category_lang'];
}

$category = new PMF_Category();
$faq      = new PMF_Faq($db, $category_lang, -1, array(-1));

$records = $faq->getAllRecordPerCategory($category_id,
                                         $faqconfig->get('records.orderby'),
                                         $faqconfig->get('records.sortby'));

$rss = new XMLWriter();
$rss->openMemory();

$rss->startDocument('1.0', $PMF_LANG['metaCharset']);
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', utf8_encode($PMF_CONF['main.titleFAQ']) . ' - ');
$rss->writeElement('description', utf8_encode($PMF_CONF['main.metaDescription']));
$rss->writeElement('link', PMF_Link::getSystemUri('/feed/category/rss.php'));

if (is_array($records)) {

    foreach ($records as $item) {

        $rss->startElement('item');
        $rss->writeElement('title', utf8_encode($item['record_title'] .
                                    ' (' . $item['visits'] . ' '.$PMF_LANG['msgViews'].')'));
        
        $rss->startElement('description');
        $rss->writeCdata(utf8_encode($item['record_preview']));
        $rss->endElement();
        
        $rss->writeElement('link', utf8_encode($item['record_link']));
        $rss->writeElement('pubDate', makeRFC822Date($item['record_date'], false));
        $rss->endElement();
    }
}

$rss->endElement();
$rssData = $rss->outputMemory();

header('Content-Type: text/xml');
header('Content-Length: '.strlen($rssData));

print $rssData;

$db->dbclose();
