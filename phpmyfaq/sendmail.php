<?php
/**
* $Id: sendmail.php,v 1.2 2005-01-09 12:15:24 thorstenr Exp $
*
* The 'send an email from the contact page' page
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

Tracking("s2fmail",0);

if (isset($_POST["name"]) && $_POST["name"] != '' && isset($_POST["email"]) && checkEmail($_POST["email"]) && isset($_POST["question"]) && $_POST["question"] != '') {
    
	list($user, $host) = explode("@", $_POST["email"]);
    if (gethostbyname($host) != "64.94.110.11") {
        $question = stripslashes($_POST["question"]);
        $name = encode_iso88591($_POST["name"]);
        $sender = $IDN->encode($_POST["email"]);
        
        mail($IDN->encode($PMF_CONF["adminmail"]), "Feedback: ".$PMF_CONF["title"], $question, "From: ".$name. " <".$sender.">");
        
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