<?php
/******************************************************************************
 * $Id: save.php,v 1.3 2004-11-27 10:58:10 thorstenr Exp $
 *
 * Datei:				save.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-16
 * Letzte Änderung:		2004-10-31
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/

if (isset($_REQUEST["username"]) && isset($_REQUEST["rubrik"]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "" && isset($_REQUEST["content"]) && $_REQUEST["content"] != "" && isset($_REQUEST["usermail"]) && $_REQUEST["usermail"] != "" && IPCheck($_SERVER["REMOTE_ADDR"])) {
	Tracking("savenewentry",0);
	$datum = date("YmdHis");
	$content = nl2br($_REQUEST["content"]);
	if (substr($_REQUEST["contentlink"],7) != "") {
		if ($PMF_CONF["ubbcode"]) {
			$content = $content."\n".$PMF_LANG["msgInfo"].$_REQUEST["contentlink"];
			}
		else {
			$content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".substr($_REQUEST["contentlink"],7)."\" target=\"_blank\">".$_REQUEST["contentlink"]."</a>";
			}
		}
	
	if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
		$lang = trim(strtolower(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)));
		}
	else {
		$lang = "en";
		}
	
	$content = safeSQL(safeHTML($content));
	$thema = safeSQL(safeHTML($_REQUEST["thema"]));
	$keywords = safeSQL($_REQUEST["keywords"]);
	$author = $_REQUEST["username"];
	$db->query("INSERT INTO ".SQLPREFIX."faqdata (lang, active, thema, content, keywords, author, email, comment, datum) VALUES ('".$lang."', 'no', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$_REQUEST["usermail"]."', 'y', '".$datum."')");
	$db->query("INSERT INTO ".SQLPREFIX."faqvisits (id, lang, visits, last_visit) VALUES ('".$db->insert_id('faqvisits', 'id')."', '".$lang."', '1', '".time()."')");
	mail($IDN->encode($PMF_CONF["adminmail"]), $PMF_CONF["title"], $PMF_LANG["msgMailCheck"], "From: ".$IDN->encode($_REQUEST["usermail"]));
	$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["msgNewContentThanks"]
				));
	}
else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
		}
	else {
		Tracking("entrypflichfeldfehler",$_GET["id"]);
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_SaveEntries"]
				));
		}
	}

$tpl->includeTemplate("writeContent", "index");

?>
