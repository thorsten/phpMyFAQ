<?php
/**
* $Id: save.php,v 1.11 2005-03-06 10:17:14 thorstenr Exp $
*
* Saves a user FAQ record and sends an email to the user
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

if (isset($_POST["username"]) && isset($_POST["rubrik"]) && is_numeric($_POST["rubrik"]) && isset($_POST["thema"]) && $_POST["thema"] != "" && isset($_POST["content"]) && $_POST["content"] != "" && isset($_POST["usermail"]) && $_POST["usermail"] != "" && IPCheck($_SERVER["REMOTE_ADDR"])) {
	
    Tracking("savenewentry",0);
	$datum = date("YmdHis");
	$content = $db->escape_string(safeSQL(safeHTML(nl2br($_POST["content"]))));
    $contentlink = $db->escape_string(safeSQL(safeHTML($_POST["contentlink"])));
	
    if (substr($contentlink,7) != "") {
		$content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".substr($contentlink,7)."\" target=\"_blank\">".$contentlink."</a>";
	}
	
	if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
		$lang = trim(strtolower(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)));
	} else {
		$lang = "en";
	}
	
	$thema = $db->escape_string(safeSQL(safeHTML($_REQUEST["thema"])));
    $selected_category = intval($_REQUEST["rubrik"]);
	$keywords = $db->escape_string(safeSQL($_REQUEST["keywords"]));
	$author = $db->escape_string(safeSQL($_REQUEST["username"]));
    $usermail = $IDN->encode($_REQUEST["usermail"]);
    
	$db->query("INSERT INTO ".SQLPREFIX."faqdata (id, lang, active, thema, content, keywords, author, email, comment, datum) VALUES (".$db->nextID(SQLPREFIX."faqdata", "id").", '".$lang."', 'no', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$usermail."', 'y', '".$datum."')");
    
    $db->query('INSERT INTO '.SQLPREFIX.'faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES ('.$selected_category.', "'.$lang.'", '.$db->insert_id('faqdata', 'id').', "'.$lang.'")');
    
	$db->query("INSERT INTO ".SQLPREFIX."faqvisits (id, lang, visits, last_visit) VALUES (".$db->insert_id('faqdata', 'id').", '".$lang."', '1', ".time().")");
    
	mail($IDN->encode($PMF_CONF["adminmail"]), unhtmlentities($PMF_CONF["title"]), unhtmlentities($PMF_LANG["msgMailCheck"])."\n".unhtmlentities($PMF_CONF["title"]).": http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]), "From: ".$usermail);
    
	$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["msgNewContentThanks"]
				));
} else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
	} else {
		Tracking("entrypflichfeldfehler", 0);
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_SaveEntries"]
				));
	}
}

$tpl->includeTemplate("writeContent", "index");
?>
