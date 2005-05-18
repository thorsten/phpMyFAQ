<?php
/**
* $Id: search.php,v 1.4 2005-05-18 17:51:53 thorstenr Exp $
*
* The fulltext search page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-16
* @copyright    (c) 2001-2005 phpMyFAQ Team
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

Tracking("fulltext_search",0);

if (isset($_REQUEST["suchbegriff"]) || isset($_GET["search"])) {
	if (isset($_REQUEST["suchbegriff"])) {
		$suchbegriff = safeSQL($_REQUEST["suchbegriff"]);
	}
	if (isset($_REQUEST["search"])) {
		$suchbegriff = safeSQL($_REQUEST["search"]);
	}
	$printResult = searchEngine($suchbegriff);
} else {
	$printResult = $PMF_LANG["help_search"];
    $suchbegriff = "";
}

$tpl->processTemplate ("writeContent", array(
				"msgSearch" => $PMF_LANG["msgSearch"],
                "searchString" => $suchbegriff,
				"writeSendAdress" => $_SERVER["PHP_SELF"]."?".$sids."action=search",
				"msgSearchWord" => $PMF_LANG["msgSearchWord"],
				"printResult" => $printResult
				));

$tpl->includeTemplate("writeContent", "index");
?>