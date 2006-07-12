<?php
/**
* $Id: rss.php,v 1.16 2006-07-12 14:39:03 matteo Exp $
*
* The RSS feed with the latest five records
*
* @package      phpMyFAQ
* @access       public
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @copyright    (c) 2004 - 2006 phpMyFAQ Team
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

require_once(PMF_ROOT_DIR.'/inc/Faq.php');

//
// get language (default: english)
//
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['detection']) ? true : false), $PMF_CONF['language']);
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$faq = new PMF_Faq($db, $LANGCODE);
$rssData = $faq->getLatestData();
$num = count($rssData);

$rss =
    "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n" .
    "<rss version=\"2.0\">\n<channel>\n" .
    "<title>".htmlspecialchars($PMF_CONF['title'])." - ".htmlspecialchars($PMF_LANG['msgLatestArticles'])."</title>\n" .
    "<description>".htmlspecialchars($PMF_CONF['metaDescription'])."</description>\n" .
    "<link>".PMF_Link::getSystemUri('/feed/latest/rss.php')."</link>";

if ($num > 0) {
    foreach ($rssData as $item) {
        // Get the url
        $link = str_replace($_SERVER['PHP_SELF'], '/index.php', $item['url']);
        if (PMF_RSS_USE_SEO) {
            if (isset($item['thema'])) {
                $oL = new PMF_Link($link);
                $oL->itemTitle = $item['thema'];
                $link = $oL->toString();
            }
        }
        // Get the content
        $content = $item['content'];
        // Fix the content internal image references
        $content = str_replace("<img src=\"/", "<img src=\"".PMF_Link::getSystemUri('/feed/latest/rss.php')."/", $content);
        $rss .= "\t<item>\n" .
                "\t\t<title><![CDATA[ " .
                $item['thema'] .
                "]]></title>\n" .
                "\t\t<description><![CDATA[ " .
                "<p><b>".$item['thema']."</b>" .
                " <em>(".$item['visits']." ".$PMF_LANG["msgViews"].")</em></p>" .
                $content .
                "]]></description>\n" .
                "\t\t<link>".PMF_Link::getSystemUri('/feed/latest/rss.php').$link."</link>\n" .
                "\t\t<pubDate>".makeRFC822Date($item['datum'])."</pubDate>\n" .
                "\t</item>\n";
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
