<?php
/**
* $Id: savecomment.php,v 1.7 2005-03-06 10:17:14 thorstenr Exp $
*
* Saves the posted comment
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-29
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

if (isset($_POST["user"]) && $_POST["user"] != "" && isset($_POST["mail"]) && $_POST["mail"] != "" && isset($_POST["comment"]) && $_POST["comment"] != "" && IPCheck($_SERVER["REMOTE_ADDR"])) {

    $id = (isset($_REQUEST["id"])) ? $_REQUEST["id"] : 0;
	Tracking("commentsave", $id);
	
	$helped = ""; // not used in this version - maybe in the future
	$datum = date("YmdHis");
	$comment = nl2br($db->escape_string(safeHTML($_REQUEST["comment"])));
    $comment_by_user = $db->escape_string(safeHTML($_REQUEST["user"]));
    $comment_by_mail = $db->escape_string(safeHTML($_REQUEST["mail"]));
	
	$result = $db->query("INSERT INTO ".SQLPREFIX."faqcomments (id_comment, id, usr, email, comment, datum, helped) VALUES (".$db->nextID(SQLPREFIX."faqcomments", "id_comment").", ".$id.",'".$comment_by_user."','".$comment_by_mail."','".$comment."','".$datum."','".$helped."')");
	
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
