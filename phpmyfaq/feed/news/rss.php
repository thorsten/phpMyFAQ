<?php
/**
 * The RSS feed with the news.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Feed
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2004-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-11-05
 */

define('PMF_ROOT_DIR', dirname(dirname(__DIR__)));
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/inc/Bootstrap.php';

//
// get language (default: english)
//
$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');
$faqConfig->setLanguage($Language);

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

$oNews          = new PMF_News($faqConfig);
$showArchive    = false;
$active         = true;
$forceConfLimit = true;
$rssData        = $oNews->getLatestData($showArchive, $active, $forceConfLimit);
$num            = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ') . ' - ' . $PMF_LANG['msgNews']);
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', $faqConfig->get('main.referenceURL'));
$rss->startElementNS('atom', 'link', 'http://www.w3.org/2005/Atom');
$rss->writeAttribute('rel', 'self');
$rss->writeAttribute('type', 'application/rss+xml');
$rss->writeAttribute('href', $faqConfig->get('main.referenceURL') . 'feed/news/rss.php');
$rss->endElement();

if ($num > 0) {
    foreach ($rssData as $item) {
        // Get the url
        $link = '/index.php?action=news&newsid=' . $item['id'] . '&newslang=' . $item['lang'];
        if (PMF_RSS_USE_SEO) {
            if (isset($item['header'])) {
                $oLink            = new PMF_Link($link, $faqConfig);
                $oLink->itemTitle = $item['header'];
                $link             = $oLink->toString();
            }
        }

        $rss->startElement('item');
        $rss->writeElement('title', html_entity_decode($item['header'], ENT_COMPAT, 'UTF-8'));

        $rss->startElement('description');
        $rss->writeCdata($item['content']);
        $rss->endElement();

        $rss->writeElement('link', $faqConfig->get('main.referenceURL') . $link);
        $rss->writeElement('guid', $faqConfig->get('main.referenceURL') . $link);
        $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['date'], true));
        $rss->endElement();
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

$headers = array(
    'Content-Type: application/rss+xml',
    'Content-Length: '.strlen($rssData)
);

$http = new PMF_Helper_Http();
$http->sendWithHeaders($rssData, $headers);

$faqConfig->getDb()->close();
