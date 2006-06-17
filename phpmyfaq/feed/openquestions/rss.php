<?php
/**
 * $Id: rss.php,v 1.2 2006-06-17 13:00:16 matteo Exp $
 *
 * The RSS feed with the latest open questions
 *
 * @package     phpMyFAQ
 * @access      public
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright   c) 2006 phpMyFAQ Team
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

require_once (PMF_ROOT_DIR."/inc/Category.php");
require_once (PMF_ROOT_DIR."/lang/".$PMF_CONF["language"]);

// Get the open questions result set with a time descedant order (the first is the last entered)
$query =   'SELECT
                id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date
            FROM
                '.SQLPREFIX.'faqquestions
            WHERE
                is_visible = \'Y\'
            ORDER BY
                ask_date DESC';
$result = $db->query($query);

$rss = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n<rss version=\"2.0\">\n<channel>\n";
$rss .= "<title>".htmlspecialchars($PMF_CONF["title"])." - ".htmlspecialchars($PMF_LANG['msgOpenQuestions'])."</title>\n";
$rss .= "<description>".htmlspecialchars($PMF_CONF["metaDescription"])."</description>\n";
$rss .= "<link>http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace ("/feed/openquestions/rss.php", "", $_SERVER["PHP_SELF"])."</link>";

if ($num = $db->num_rows($result) > 0) {
    $counter = 0;
    while (($row = $db->fetch_object($result)) && $counter < PMF_RSS_OPENQUESTIONS_MAX) {
        $counter++;
        $rss .= "\t<item>\n";
        // Get the content
        $content = $row->ask_content;
        $rss .= "\t\t<title><![CDATA[".makeShorterText($row->ask_content, 8)." (".$row->ask_username.")]]></title>\n";
        $rss .= "\t\t<description><![CDATA[".$content."]]></description>\n";
        // Let the PMF administrator manually choose between (1) (default) and (2)
        // 1. This link below goes to the "Open questions page"
        $rss .= "\t\t<link>http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER["PHP_SELF"])."?action=open#openq_".$row->id."</link>\n";
        // 2. This link below is a shortcut for a fast reply ("Add content" page within the context of the current question)
        //$rss .= "\t\t<link><![CDATA[http".(isset($_SERVER['HTTPS']) ? 's' : '')."://".$_SERVER["HTTP_HOST"].str_replace("feed/openquestions/rss.php", "index.php", $_SERVER["PHP_SELF"])."?action=add&amp;question=".rawurlencode($content)."&amp;cat=".$row->ask_rubrik."]]></link>\n";
        $rss .= "\t\t<pubDate>".makeRFC822Date($row->ask_date)."</pubDate>\n";
        $rss .= "\t</item>\n";
    }
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
