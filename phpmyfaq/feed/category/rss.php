<?php
/**
 * The RSS feed for categories.
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Feed
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2008-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-25
 */

define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));
define('IS_VALID_PHPMYFAQ', null);

require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqConfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

// Preload English strings
require_once PMF_ROOT_DIR . '/lang/language_en.php';

if ($faqConfig->get('security.enableLoginOnly')) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="phpMyFAQ RSS Feeds"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    } else {
        $user = new PMF_User_CurrentUser();
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
    $user = PMF_User_CurrentUser::getFromSession($faqConfig->get('security.ipCheck'));
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof PMF_User_CurrentUser) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_PermMedium) {
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

$category_id = PMF_Filter::filterInput(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$category    = new PMF_Category($current_user, $current_groups);
$faq         = new PMF_Faq($current_user, $current_groups);

$records = $faq->getAllRecordPerCategory($category_id,
                                         $faqConfig->get('records.orderby'),
                                         $faqConfig->get('records.sortby'));

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', 'utf-8');
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', $faqConfig->get('main.titleFAQ') . ' - ');
$rss->writeElement('description', html_entity_decode($faqConfig->get('main.metaDescription')));
$rss->writeElement('link', PMF_Link::getSystemUri('/feed/category/rss.php'));

if (is_array($records)) {

    foreach ($records as $item) {
        
        $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['record_link']);

        if (PMF_RSS_USE_SEO) {
            if (isset($item['record_title'])) {
                $oLink            = new PMF_Link($link);
                $oLink->itemTitle = $item['record_title'];
                $link             = $oLink->toString();
           }
        }

        $rss->startElement('item');
        $rss->writeElement('title', html_entity_decode($item['record_title'] .
                                    ' (' . $item['visits'] . ' '.$PMF_LANG['msgViews'].')', ENT_COMPAT, 'UTF-8'));
        
        $rss->startElement('description');
        $rss->writeCdata($item['record_preview']);
        $rss->endElement();
        
        $rss->writeElement('link', PMF_Link::getSystemUri('/feed/category/rss.php') . $link); 
        $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['record_date'], true));

        $rss->endElement();
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

header('Content-Type: application/rss+xml');
header('Content-Length: '.strlen($rssData));

print $rssData;

$db->close();