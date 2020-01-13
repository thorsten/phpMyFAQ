<?php

/**
 * The RSS feed with the latest five records.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 */

define('PMF_ROOT_DIR', dirname(dirname(__DIR__)));
define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/src/Bootstrap.php';

//
// get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

// Preload English strings
require_once PMF_ROOT_DIR.'/lang/language_en.php';
$faqConfig->setLanguage($Language);

if (isset($faqLangCode) && Language::isASupportedLanguage($faqLangCode)) {
    // Overwrite English strings with the ones we have in the current language
    require_once PMF_ROOT_DIR.'/lang/language_'.$faqLangCode.'.php';
} else {
    $faqLangCode = 'en';
}

if ($faqConfig->get('security.enableLoginOnly')) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="phpMyFAQ RSS Feeds"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    } else {
        $user = new CurrentUser($faqConfig);
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
    $user = CurrentUser::getFromCookie($faqConfig);
    if (!$user instanceof CurrentUser) {
        $user = CurrentUser::getFromSession($faqConfig);
    }
}

//
// Get current user and group id - default: -1
//
if (isset($user) && !is_null($user) && $user instanceof CurrentUser) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = [-1];
    }
    if (0 == count($currentGroups)) {
        $currentGroups = [-1];
    }
} else {
    $currentUser = -1;
    $currentGroups = [-1];
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

if (!$faqConfig->get('main.enableRssFeeds')) {
    exit('The RSS Feeds are disabled.');
}

$faq = new Faq($faqConfig);
$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

$rssData = $faq->getLatestData(PMF_NUMBER_RECORDS_LATEST);
$num = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ').' - '.$PMF_LANG['msgLatestArticles']);
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', $faqConfig->getDefaultUrl());
$rss->startElementNs('atom', 'link', 'http://www.w3.org/2005/Atom');
$rss->writeAttribute('rel', 'self');
$rss->writeAttribute('type', 'application/rss+xml');
$rss->writeAttribute('href', $faqConfig->getDefaultUrl().'feed/latest/rss.php');
$rss->endElement();

if ($num > 0) {
    foreach ($rssData as $item) {
        // Get the url
        $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['url']);
        if (PMF_RSS_USE_SEO) {
            if (isset($item['question'])) {
                $oLink = new Link($link, $faqConfig);
                $oLink->itemTitle = html_entity_decode($item['question'], ENT_COMPAT, 'UTF-8');
                $link = html_entity_decode($oLink->toString(), ENT_COMPAT, 'UTF-8');
            }
        }
        // Get the content
        $content = $item['answer'];
        // Fix the content internal image references
        $content = str_replace('<img src="/', '<img src="'.$faqConfig->getDefaultUrl(), $content);

        $rss->startElement('item');
        $rss->writeElement('title', html_entity_decode($item['question'], ENT_COMPAT, 'UTF-8'));

        $rss->startElement('description');
        $rss->writeCdata($content);
        $rss->endElement();

        $rss->writeElement('link', $link);
        $rss->writeElement('guid', $link);
        $rss->writeElement('pubDate', Date::createRFC822Date($item['date'], true));
        $rss->endElement();
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

$headers = [
    'Content-Type: application/rss+xml',
    'Content-Length: '.strlen($rssData),
];

$http = new HttpHelper();
$http->sendWithHeaders($rssData, $headers);

$faqConfig->getDb()->close();
