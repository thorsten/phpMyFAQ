<?php
/**
 * The RSS feed with the latest open questions.
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
 * @copyright 2006-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-17
 */

use Symfony\Component\HttpFoundation\Response;

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

$faq     = new PMF_Faq($faqConfig);
$rssData = $faq->getAllOpenQuestions(false);
$num     = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ') . ' - ' . $PMF_LANG['msgOpenQuestions']);
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', $faqConfig->get('main.referenceURL'));

if ($num > 0) {
    $counter = 0;
    foreach ($rssData as $item) {
        if ($counter < PMF_RSS_OPENQUESTIONS_MAX) {
            $counter++;

            $rss->startElement('item');
            $rss->writeElement('title', PMF_Utils::makeShorterText(html_entity_decode($item['question'], ENT_COMPAT, 'UTF-8'), 8) .
                                        " (".$item['username'].")");

            $rss->startElement('description');
            $rss->writeCdata($item['question']);
            $rss->endElement();

            $rss->writeElement('link', (isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER['SCRIPT_NAME'])."?action=open#openq_".$item['id']);
            $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['created'], true));
            $rss->endElement();
        }
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

$response = Response::create($rssData);
$response->headers->set('Content-Type', 'application/rss+xml');
$response->headers->set('Content-Length', strlen($rssData));
$response->send();

$faqConfig->getDb()->close();
