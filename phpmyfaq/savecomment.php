<?php
/**
* $Id: savecomment.php,v 1.3 2004-12-11 20:06:32 thorstenr Exp $
*
* Saves the posted comment
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-29
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

if (isset($_POST["user"]) && isset($_POST["mail"]) && isset($_POST["comment"]) && IPCheck($_SERVER["REMOTE_ADDR"])) {
	Tracking("commentsave", $_REQUEST["id"]);
	
	$helped = ""; // not used in this version - maybe in the future
	$datum = date("YmdHis");
	$comment = nl2br(addslashes(safeHTML($_REQUEST["comment"])));
	
	$result = $db->query("INSERT INTO ".SQLPREFIX."faqcomments (id_comment, id, usr, email, comment, datum, helped) VALUES (".$db->insert_id(SQLPREFIX."faqcomments", "id_comment").", ".$_REQUEST["id"].",'".$_REQUEST["user"]."','".$_REQUEST["mail"]."','".$comment."','".$datum."','".$helped."')");
	
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
