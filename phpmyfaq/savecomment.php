<?php
/******************************************************************************
 * Datei:				savecomment.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-08-29
 * Letzte Änderung:		2004-05-29
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

if ($_REQUEST["user"] && $_REQUEST["mail"] && $_REQUEST["comment"] && IPCheck($_SERVER["REMOTE_ADDR"])) {
	Tracking("commentsave", $_REQUEST["id"]);
	
	$helped = ""; // not used in this version - maybe in the future
	$datum = date("YmdHis");
	$comment = nl2br(addslashes(safeHTML($_REQUEST["comment"])));
	
	$result = $db->query("INSERT INTO ".SQLPREFIX."faqcomments (id, usr, email, comment, datum, helped) VALUES ('".$_REQUEST["id"]."','".$_REQUEST["user"]."','".$_REQUEST["mail"]."','".$comment."','".$datum."','".$helped."')");
	
	$tpl->processTemplate ("writeContent", array(
				"msgCommentHeader" => $PMF_LANG["msgWriteComment"],
				"Message" => $PMF_LANG["msgCommentThanks"]
				));
	}
else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgCommentHeader" => $PMF_LANG["msgWriteComment"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
		}
	else {
		Tracking("commentpflichtfeldfehler", $_GET["id"]);
		$tpl->processTemplate ("writeContent", array(
				"msgCommentHeader" => $PMF_LANG["msgWriteComment"],
				"Message" => $PMF_LANG["err_SaveComment"]
				));
		}
	}

$tpl->includeTemplate("writeContent", "index");
?>
