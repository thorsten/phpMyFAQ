<?php
/******************************************************************************
 * Datei:				sendmail.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-16
 * Letzte Änderung:		2004-07-17
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

if ($_POST["name"] && $_POST["email"] && checkEmail($_POST["email"]) && $_POST["question"]) {
	list($user, $host) = explode("@", $_POST["email"]);
    if (gethostbyname($host) != "64.94.110.11") {
        mail($IDN->encode($PMF_CONF["adminmail"]), "Feedback: ".$PMF_CONF["title"], stripslashes($_POST["question"]), "From: ".encode_iso88591($_POST["name"]). " <".$IDN->encode($_POST["email"]).">");
        $tpl->processTemplate ("writeContent", array(
                "msgContact" => $PMF_LANG["msgContact"],
                "Message" => $PMF_LANG["msgMailContact"]
                ));
        }
    else {
        $tpl->processTemplate ("writeContent", array(
                "msgContact" => $PMF_LANG["msgContact"],
                "Message" => $PMF_LANG["err_noMailAdress"]
                ));
		}
	}
else {
	$tpl->processTemplate ("writeContent", array(
				"msgContact" => $PMF_LANG["msgContact"],
				"Message" => $PMF_LANG["err_sendMail"]
				));
	}

$tpl->includeTemplate("writeContent", "index");
?>