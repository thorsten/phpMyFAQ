<?php
/**
* $Id: rss.php,v 1.16 2007-03-29 18:47:40 thorstenr Exp $
*
* The RSS feed with the news
*
* @package      phpMyFAQ
* @access       public
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <mattao@scaramuccia.com>
* @copyright    (c) 2004-2006 phpMyFAQ Team
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
session_name('pmfauth' . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

require_once(PMF_ROOT_DIR.'/inc/News.php');

//
// get language (default: english)
//
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['main.languageDetection']) ? true : false), $PMF_CONF['main.language']);
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$oNews = new PMF_News($db, $LANGCODE);
$showArchive    = false;
$active         = true;
$forceConfLimit = true;
$rssData = $oNews->getLatestData($showArchive, $active, $forceConfLimit);
$num = count($rssData);

$rss =
    "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n" .
    "<rss version=\"2.0\">\n<channel>\n" .
    "<title>".htmlspecialchars($PMF_CONF['main.titleFAQ'])." - ".htmlspecialchars($PMF_LANG['msgNews'])."</title>\n" .
    "<description>".htmlspecialchars($PMF_CONF['main.metaDescription'])."</description>\n" .
    "<link>".PMF_Link::getSystemUri('/feed/latest/rss.php')."</link>";

if ($num > 0) {
    foreach ($rssData as $item) {
        // Get the url
        $link = '/index.php?action=news&amp;newsid='.$item['id'].'&amp;newslang='.$item['lang'];
        if (PMF_RSS_USE_SEO) {
            if (isset($item['header'])) {
                $oL = new PMF_Link($link);
                $oL->itemTitle = $item['header'];
                $link = $oL->toString();
            }
        }
        $rss .= "\t<item>\n" .
                "\t\t<title><![CDATA[" .
                $item['header'] .
                "]]></title>\n" .
                "\t\t<description><![CDATA[" .
                $item['content'] .
                "]]></description>\n" .
                "\t\t<link>".PMF_Link::getSystemUri('/feed/news/rss.php').$link."</link>\n" .
                "\t\t<pubDate>".makeRFC822Date($item['date'])."</pubDate>\n" .
                "\t</item>\n";
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
