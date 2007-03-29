<?php
/**
 * $Id: rss.php,v 1.9 2007-03-29 18:47:40 thorstenr Exp $
 *
 * The RSS feed with the latest open questions
 *
 * @package     phpMyFAQ
 * @access      public
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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
session_name('pmf_auth_'.$faqconfig->get('main.phpMyFAQToken'));
session_start();

require_once(PMF_ROOT_DIR.'/inc/Faq.php');

//
// get language (default: english)
//
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['main.languageDetection']) ? true : false), $PMF_CONF['language']);
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$rss = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG['metaCharset']."\" standalone=\"yes\" ?>\n<rss version=\"2.0\">\n<channel>\n";
$rss .= "<title>".htmlspecialchars($PMF_CONF['main.titleFAQ'])." - ".htmlspecialchars($PMF_LANG['msgOpenQuestions'])."</title>\n";
$rss .= "<description>".htmlspecialchars($PMF_CONF['main.metaDescription'])."</description>\n";
$rss .= "<link>".PMF_Link::getSystemUri('/feed/openquestions/rss.php')."</link>\n";

$faq = new PMF_Faq($db, $LANGCODE);
$rssData = $faq->getAllOpenQuestions();
$num = count($rssData);

if ($num > 0) {
    $counter = 0;
    foreach ($rssData as $item) {
        if ($counter < PMF_RSS_OPENQUESTIONS_MAX) {
            $counter++;
            $rss .= "\t<item>\n";
            $content = $item['question'];
            $rss .= "\t\t<title><![CDATA[".makeShorterText($item['question'], 8)." (".$item['user'].")]]></title>\n";
            $rss .= "\t\t<description><![CDATA[".$content."]]></description>\n";
            $rss .= "\t\t<link>http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER["PHP_SELF"])."?action=open#openq_".$item['id']."</link>\n";
            $rss .= "\t\t<pubDate>".makeRFC822Date($item['date'])."</pubDate>\n";
            $rss .= "\t</item>\n";
        }
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;
exit();