<?php
/******************************************************************************

 * Datei:				mailsend2friend.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-16
 * Letzte Änderung:		2004-06-27
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

Tracking("s2fmail",0);

if (isset($_POST["name"]) && isset($_POST["mailfrom"]) && IPCheck($_SERVER["REMOTE_ADDR"])) {
	$mailto = $_POST["mailto"];
	list($user, $host) = explode("@", $mailto[0]);
    if (checkEmail($_REQUEST["mailfrom"])) {
        foreach($mailto as $mail) {
            if ($mail != "") {
                mail($IDN->encode($mail), $PMF_LANG["msgS2FMailSubject"].$_POST["name"], $PMF_LANG["send2friend_text"]."\n\n".$PMF_LANG["msgS2FText2"]."\n".$_POST["link"]."\n\n".stripslashes($_POST["zusatz"]), "From: ".$IDN->encode($_POST["mailfrom"]));
                usleep(500);
            }
        }
        $tpl->processTemplate ("writeContent", array(
				"msgSend2Friend" => $PMF_LANG["msgSend2Friend"],
				"Message" => $PMF_LANG["msgS2FThx"]
    				));
    } else {
		$tpl->processTemplate ("writeContent", array(
				"msgSend2Friend" => $PMF_LANG["msgSend2Friend"],
				"Message" => $PMF_LANG["err_noMailAdress"]
				));
    }
} else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgSend2Friend" => $PMF_LANG["msgSend2Friend"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
    } else {
		$tpl->processTemplate ("writeContent", array(
				"msgSend2Friend" => $PMF_LANG["msgSend2Friend"],
				"Message" => $PMF_LANG["err_sendMail"]
				));
    }
}

$tpl->includeTemplate("writeContent", "index");
?>