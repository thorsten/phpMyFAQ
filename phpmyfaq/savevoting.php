<?php
/******************************************************************************
 * $Id: savevoting.php,v 1.6 2004-12-16 13:34:55 thorstenr Exp $
 *
 * Datei:				savevoting.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-16
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

if (isset($_POST["vote"]) && $_POST["vote"] != "" && votingCheck($_POST["artikel"], $_POST["userip"]) && intval($_POST["vote"]) > 0 && intval($_POST["vote"]) < 6) {
	Tracking("savevoting", $_POST["artikel"]);
	$noUser = "0";
	$datum = date("YmdHis");
	if ($result = $db->query("SELECT usr FROM ".SQLPREFIX."faqvoting WHERE artikel = ".$_POST["artikel"])) {
		while ($row = $db->fetch_object($result)) {
			$noUser = $row->user;
			}
		}
	if ($noUser == "0" || $noUser == "") {
		$db->query("INSERT INTO ".SQLPREFIX."faqvoting (id, artikel, vote, usr, datum, ip) VALUES (".$db->nextID(SQLPREFIX."faqvoting", "id").", ".$_POST["artikel"].", ".$_POST["vote"].", '1', ".time().", '".$_POST["userip"]."');");
		}
    else {
		$db->query("UPDATE ".SQLPREFIX."faqvoting SET vote = vote + ".$_POST["vote"].", usr = user + 1, datum = ".time().", ip = '".$_POST["userip"]."' where artikel = ".$_POST["artikel"]);
		}
	$tpl->processTemplate ("writeContent", array(
				"msgVoteThanks" => $PMF_LANG["msgVoteThanks"]
				));
	}
elseif(isset($_POST["vote"])  && !votingCheck($_POST["artikel"], $_POST["userip"])) {
    Tracking("savevotingerror", $_POST["artikel"]);
	$tpl->processTemplate ("writeContent", array(
				"msgVoteThanks" => $PMF_LANG["err_VoteTooMuch"]
				));
    }
else
	{
	Tracking("savevotingerror", $_POST["artikel"]);
	$tpl->processTemplate ("writeContent", array(
				"msgVoteThanks" => $PMF_LANG["err_noVote"]
				));
	}

$tpl->includeTemplate("writeContent", "index");
?>
>>>>>>> 1.5
