<?php
/**
* $Id: rss.php,v 1.13 2006-06-18 07:38:45 matteo Exp $
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

$faq = new FAQ($db, $LANGCODE);
$rssData = $faq->getLatestData();
$num = count($rssData);

$rss =
    "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n" .
    "<rss version=\"2.0\">\n<channel>\n" .
    "<title>".$PMF_CONF["title"]."</title>\n" .
    "<description>".$PMF_CONF["metaDescription"]."</description>\n" .
    "<link>http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"]."</link>";

if ($num > 0) {
    foreach ($rssData as $rssItem) {
        // Get the content
        $content = $rssItem['content'];
        // Fix the content internal image references
        $content = str_replace("<img src=\"/", "<img src=\"http".(isset($_SERVER["HTTPS"]) ? "s" : "")."://".$_SERVER["HTTP_HOST"]."/", $content);
        $rss .= "\t<item>\n" .
                "\t\t<title><![CDATA[ " .
                $rssItem['thema'] .
                "]]></title>\n" .
                "\t\t<description><![CDATA[ " .
                "<p><b>".$rssItem['thema']."</b>" .
                " <em>(".$rssItem['visits']." ".$PMF_LANG["msgViews"].")</em></p>" .
                $content .
                "]]></description>\n" .
                "\t\t<link>http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].$rssItem['url']."</link>\n" .
                "\t\t<pubDate>".makeRFC822Date($rssItem['datum'])."</pubDate>\n" .
                "\t</item>\n";
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
