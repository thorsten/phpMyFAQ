<?php
/**
* $Id: rss.php,v 1.4 2004-11-24 21:34:24 thorstenr Exp $
*
* The RSS feed with the news
*
* @package  phpMyFAQ
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
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

define("PMF_ROOT_DIR", dirname(dirname(dirname(__FILE__))));

/* read configuration, include classes and functions */
require_once (PMF_ROOT_DIR."/inc/data.php");
require_once (PMF_ROOT_DIR."/inc/db.php");
define("SQLPREFIX", $DB["prefix"]);
$db = new db($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
require_once (PMF_ROOT_DIR."/inc/config.php");
require_once (PMF_ROOT_DIR."/inc/constants.php");
require_once (PMF_ROOT_DIR."/inc/category.php");
require_once (PMF_ROOT_DIR."/inc/functions.php");
require_once (PMF_ROOT_DIR."/lang/language_en.php");

$result = $db->query("SELECT datum, header, artikel, link, linktitel, target FROM ".SQLPREFIX."faqnews ORDER BY datum desc LIMIT 0,".$PMF_CONF["numNewsArticles"]);

$rss = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n<rss version=\"2.0\">\n<channel>\n";
$rss .= "<title>".$PMF_CONF["title"]."</title>\n";
$rss .= "<description>".$PMF_CONF["metaDescription"]."</description>\n";
$rss .= "<link>http://".$_SERVER["HTTP_HOST"]."</link>";

if ($db->num_rows($result) > 0) {
    
    while ($row = $db->fetch_object($result)) {
        
        $rss .= "\t<item>\n";
        $rss .= "\t\t<title>".$row->header."</title>\n";
        $rss .= "\t\t<description>".stripslashes(htmlspecialchars($row->artikel))."</description>\n";
        $rss .= "\t\t<link>http://".$_SERVER["HTTP_HOST"].str_replace ("feed/news/rss.php", "", $_SERVER["PHP_SELF"])."</link>\n";
        $rss .= "\t</item>\n";
        
    }
    
}

$rss .= "</channel>\n</rss>";

header("Content-Type: text/xml");
header("Content-Length: ".strlen($rss));
print $rss;

$db->dbclose();
?>