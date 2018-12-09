<?php

/**
 * The RSS feed for categories.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2008-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-25
 */

define('PMF_ROOT_DIR', dirname(dirname(__DIR__)));
define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Category;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
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
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

//
// Initalizing static string wrapper
//
Strings::init($LANGCODE);

// Preload English strings
require_once PMF_ROOT_DIR.'/lang/language_en.php';
$faqConfig->setLanguage($Language);

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
    $current_user = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $current_user = -1;
    $current_groups = array(-1);
}

if (!$faqConfig->get('main.enableRssFeeds')) {
    exit();
}

$category_id = Filter::filterInput(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$category = new Category($faqConfig);
$category->setUser($current_user);
$category->setGroups($current_groups);

$faq = new Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

$records = $faq->getAllRecordPerCategory(
    $category_id,
    $faqConfig->get('records.orderby'),
    $faqConfig->get('records.sortby')
);

$rss = new \XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ').' - ');
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', $faqConfig->getDefaultUrl());
$rss->startElementNS('atom', 'link', 'http://www.w3.org/2005/Atom');
$rss->writeAttribute('rel', 'self');
$rss->writeAttribute('type', 'application/rss+xml');
$rss->writeAttribute('href', $faqConfig->getDefaultUrl().'feed/category/rss.php');
$rss->endElement();

if (is_array($records)) {
    foreach ($records as $item) {
        $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['record_link']);

        if (PMF_RSS_USE_SEO) {
            if (isset($item['record_title'])) {
                $oLink = new Link($link, $faqConfig);
                $oLink->itemTitle = $item['record_title'];
                $link = $oLink->toString();
            }
        }

        $rss->startElement('item');
        $rss->writeElement('title', html_entity_decode($item['record_title'].
                                    ' ('.$item['visits'].' '.$PMF_LANG['msgViews'].')', ENT_COMPAT, 'UTF-8'));

        $rss->startElement('description');
        $rss->writeCdata($item['record_preview']);
        $rss->endElement();

        $rss->writeElement('link', $link);
        $rss->writeElement('guid', $link);

        $rss->writeElement('pubDate', Date::createRFC822Date($item['record_updated'], true));

        $rss->endElement();
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

$headers = array(
    'Content-Type: application/rss+xml',
    'Content-Length: '.strlen($rssData),
);

$http = new HttpHelper();
$http->sendWithHeaders($rssData, $headers);

$faqConfig->getDb()->close();
