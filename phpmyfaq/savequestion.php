<?php
/**
* $Id: savequestion.php,v 1.2 2004-11-21 11:05:37 thorstenr Exp $
*
* @author           Thorsten Rinne <thorsten@phpmyfaq.de>
* @author           David Saez Padros <david@ols.es>
* @since            2002-09-17
* @copyright        (c) 2001-2004 phpMyFAQ Team
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

if ($_REQUEST["username"] && $_REQUEST["usermail"] && $_REQUEST["content"] && IPCheck($_SERVER["REMOTE_ADDR"])) {

	if (isset($_POST['try_search'])) {
        
        $suchbegriff = safeSQL($_POST['content']);
		$printResult = searchEngine($suchbegriff, $numr);
        echo $numr;
    } else {
        
        $numr = 0;
    }

	if ($numr == 0) {
        
        $cat = new category;
        $categories = $cat->getAllCategories();
    	list($user, $host) = explode("@", $_REQUEST["usermail"]);
        if (checkEmail($_REQUEST["usermail"]) && gethostbyname($host) != "64.94.110.11") {
            $datum = date("YmdHis");
            $content  = addslashes($_REQUEST["content"]);
            
            $result = $db->query("INSERT INTO ".SQLPREFIX."faqfragen (ask_username, ask_usermail, ask_rubrik, ask_content, ask_date) VALUES ('".$_REQUEST["username"]."', '".$_REQUEST["usermail"]."', '".$_REQUEST["rubrik"]."', '".$content."', '".$datum."')");
            
            $questionMail = "User: ".$_REQUEST["username"].", mailto:".$_REQUEST["usermail"]."\n".$PMF_LANG["msgCategory"].":  ".$categories[$_REQUEST["rubrik"]]["name"]."\n\n".wordwrap(stripslashes($content), 72);
            
            mail($IDN->encode($PMF_CONF["adminmail"]), $PMF_CONF["title"], strip_tags($questionMail), "From: ".encode_iso88591($_REQUEST["username"])."<".$IDN->encode($_REQUEST["usermail"]).">");
            
            $tpl->processTemplate ("writeContent", array(
                    "msgQuestion" => $PMF_LANG["msgQuestion"],
                    "Message" => $PMF_LANG["msgAskThx4Mail"]
                    ));
        } else {
            $tpl->processTemplate ("writeContent", array(
                    "msgQuestion" => $PMF_LANG["msgQuestion"],
                    "Message" => $PMF_LANG["err_noMailAdress"]
                    ));
        }
        
    } else {
        
        $tpl->templates['writeContent'] = $tpl->readTemplate('template/asksearch.tpl');
        
		$tpl->processTemplate ('writeContent', array(
			'msgQuestion' => $PMF_LANG["msgQuestion"],
            'printResult' => $printResult,
            'msgAskYourQuestion' => $PMF_LANG['msgAskYourQuestion'],
            'msgContent' => $_POST['content'],
            'postUsername' => urlencode($_REQUEST['username']),
            'postUsermail' => urlencode($_REQUEST['usermail']),
            'postRubrik' => urlencode($_REQUEST['rubrik']),
            'postContent' => urlencode($_REQUEST['content']),
            'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=savequestion',
			));
    }
} else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgQuestion" => $PMF_LANG["msgQuestion"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
	} else {
		$tpl->processTemplate ("writeContent", array(
				"msgQuestion" => $PMF_LANG["msgQuestion"],
				"Message" => $PMF_LANG["err_SaveQuestion"]
				));
    }
}

$tpl->includeTemplate("writeContent", "index");
?>