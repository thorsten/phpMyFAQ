<?php
/**
* $Id: rss.php,v 1.20 2007-03-29 12:03:51 thorstenr Exp $
*
* The RSS feed with the top ten
*
* @package      phpMyFAQ
* @access       public
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
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
session_name('pmf_auth_'.$faqconfig->get('phpMyFAQToken'));
session_start();

require_once(PMF_ROOT_DIR.'/inc/Faq.php');

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

$cat = null;
$lang = null;
if (isset($_GET['cat']) && is_numeric($_GET['cat']) && ($_GET['cat'] != 0)) {
    $cat = $_GET['cat'];
}
if (isset($_GET['lang']) && PMF_Init::isASupportedLanguage($_GET['lang'])) {
    $lang = $_GET['lang'];
}

$faq = new PMF_Faq($db, $LANGCODE);
$rssData = $faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN, $cat, $lang);
$num = count($rssData);

$rss =
    "<?xml version=\"1.0\" encoding=\"".$PMF_LANG['metaCharset']."\" standalone=\"yes\" ?>\n" .
    "<rss version=\"2.0\">\n<channel>\n" .
    "<title>".htmlspecialchars($PMF_CONF['title'])." - ".htmlspecialchars($PMF_LANG['msgTopTen'])."</title>\n" .
    "<description>".htmlspecialchars($PMF_CONF['main.metaDescription'])."</description>\n" .
    "<link>".PMF_Link::getSystemUri('/feed/topten/rss.php')."</link>";

if ($num > 0) {
    $i = 0;
    foreach ($rssData as $item) {
        $i++;
        // Get the url
        $link = str_replace($_SERVER['PHP_SELF'], '/index.php', $item['url']);
        if (PMF_RSS_USE_SEO) {
            if (isset($item['thema'])) {
                $oL = new PMF_Link($link);
                $oL->itemTitle = $item['thema'];
                $link = $oL->toString();
            }
        }
        $rss .= "\t<item>\n" .
                "\t\t<title><![CDATA[" .
                makeShorterText($item['thema'], 8)." (".$item['visits']." ".$PMF_LANG['msgViews'].")" .
                "]]></title>\n" .
                "\t\t<description><![CDATA[" .
                "[".$i.".] ".$item['thema']." (".$item['visits']." ".$PMF_LANG['msgViews'].")" .
                "]]></description>\n" .
                "\t\t<link>".PMF_Link::getSystemUri('/feed/topten/rss.php').$link."</link>\n" .
                "\t\t<!-- The real FAQ publication date -->\n" .
                // datum is a phpMyFAQ date
                "\t\t<!-- ".makeRFC822Date($item['date'])." -->\n" .
                // last_visit is a mktime timpestamp date
                "\t\t<pubDate>".makeRFC822Date($item['last_visit'], false)."</pubDate>\n" .
                "\t</item>\n";
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
