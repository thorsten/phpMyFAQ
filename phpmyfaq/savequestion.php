<?php
/**
* $Id: savequestion.php,v 1.12 2005-08-19 20:10:48 thorstenr Exp $
*
* @author           Thorsten Rinne <thorsten@phpmyfaq.de>
* @author           David Saez Padros <david@ols.es>
* @author           Jürgen Kuza <kig@bluewin.ch>
* @since            2002-09-17
* @copyright        (c) 2001-2005 phpMyFAQ Team
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

if (isset($_REQUEST["username"]) && $_REQUEST["username"] != '' && isset($_REQUEST["usermail"]) && checkEmail($_REQUEST["usermail"]) && isset($_REQUEST["content"]) && $_REQUEST["content"] != '' && IPCheck($_SERVER["REMOTE_ADDR"])) {

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
        $usermail = $IDN->encode($_REQUEST["usermail"]);
        $username = strip_tags($_REQUEST["username"]);
        $selected_category = intval($_REQUEST["rubrik"]);
        
    	list($user, $host) = explode("@", $usermail);
        if (checkEmail($usermail)) {
            $datum = date("YmdHis");
            $content  = $db->escape_string(strip_tags($_REQUEST["content"]));
            
            $result = $db->query("INSERT INTO ".SQLPREFIX."faqfragen (id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date) VALUES (".$db->nextID(SQLPREFIX."faqfragen", "id").", '".$db->escape_string($username)."', '".$db->escape_string($usermail)."', ".$selected_category.", '".$content."', '".$datum."')");
            
            $questionMail = "User: ".$username.", mailto:".$usermail."\n".$PMF_LANG["msgCategory"].":  ".$categories[$selected_category]["name"]."\n\n".wordwrap(stripslashes($content), 72);
            
            $headers = '';
            $db->query("SELECT ".SQLPREFIX."faquser.email FROM ".SQLPREFIX."faqcategories INNER JOIN ".SQLPREFIX."faquser ON ".SQLPREFIX."faqcategories.user_id = ".SQLPREFIX."faquser.id WHERE ".SQLPREFIX."faqcategories.id = ".$selected_category);
            while ($row = $db->fetch_object($result)) {
                $headers .= "CC: ".$row->email."\n";
            }
            
            mail($IDN->encode($PMF_CONF["adminmail"]), $PMF_CONF["title"], strip_tags($questionMail), "From: ".encode_iso88591($username)."<".$IDN->encode($usermail).">", $headers);
            
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