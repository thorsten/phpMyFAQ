<?php
/**
 * The RSS feed with the latest five records.
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
 * @copyright 2004-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
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
    require_once PMF_ROOT_DIR . '/lang/language_' . $LANGCODE . '.php';
} else {
    $LANGCODE = 'en';
}

if ($faqConfig->get('security.enableLoginOnly')) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        $response = Response::create()
            ->setStatusCode(401);
        $response->headers->set('WWW-Authenticate', 'Basic realm="phpMyFAQ RSS Feeds"');
        $response->send();
    } else {
        $user = new PMF_User_CurrentUser($faqConfig);
        if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            if ($user->getStatus() != 'blocked') {
                $auth = true;
            } else {
                $user = null;
            }
        } else {
            $user = null;
        }
    }
} else {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}

//
// Get current user and group id - default: -1
//
if (isset($user) && !is_null($user) && $user instanceof PMF_User_CurrentUser) {
    $current_user = $user->getUserId();
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

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

$faq = new PMF_Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

$rssData = $faq->getLatestData(PMF_NUMBER_RECORDS_LATEST);
$num     = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ') . ' - ' . $PMF_LANG['msgLatestArticles']);
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', $faqConfig->get('main.referenceURL'));

if ($num > 0) {
    foreach ($rssData as $item) {
        // Get the url
        $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['url']);
        if (PMF_RSS_USE_SEO) {
            if (isset($item['thema'])) {
                $oLink            = new PMF_Link($link, $faqConfig);
                $oLink->itemTitle = html_entity_decode($item['thema'], ENT_COMPAT, 'UTF-8');
                $link             = html_entity_decode($oLink->toString(), ENT_COMPAT, 'UTF-8');
            }
        }
        // Get the content
        $content = $item['content'];
        // Fix the content internal image references
        $content = str_replace("<img src=\"/", "<img src=\"".$faqConfig->get('main.referenceURL')."/", $content);

        $rss->startElement('item');
        $rss->writeElement('title', html_entity_decode($item['thema'], ENT_COMPAT, 'UTF-8'));

        $rss->startElement('description');
        $rss->writeCdata($content);
        $rss->endElement();

        $rss->writeElement('link', $faqConfig->get('main.referenceURL') . $link);
        $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['datum'], true));
        $rss->endElement();
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
