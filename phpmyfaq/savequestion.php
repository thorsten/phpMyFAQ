<?php
/**
* $Id: savequestion.php,v 1.19 2006-06-11 15:26:21 matteo Exp $
*
* @author           Thorsten Rinne <thorsten@phpmyfaq.de>
* @author           David Saez Padros <david@ols.es>
* @author           Jürgen Kuza <kig@bluewin.ch>
* @since            2002-09-17
* @copyright        (c) 2001-2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);

if (    isset($_POST['username']) && $_POST['username'] != ''
     && isset($_POST['usermail']) && checkEmail($_POST['usermail'])
     && isset($_POST['content']) && $_POST['content'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['content'])))
     && isset($_POST['captcha']) && ($captcha->validateCaptchaCode($_POST['captcha'])) ) {

	if (isset($_POST['try_search'])) {
        $suchbegriff = safeSQL($_POST['content']);
		$printResult = searchEngine($suchbegriff, $numr);
        echo $numr;
    } else {
        $numr = 0;
    }

	if ($numr == 0) {
        
        $cat = new PMF_Category;
        $categories = $cat->getAllCategories();
        $usermail = $IDN->encode($_REQUEST["usermail"]);
        $username = strip_tags($_REQUEST["username"]);
        $selected_category = intval($_REQUEST["rubrik"]);
        
    	list($user, $host) = explode("@", $usermail);
        if (checkEmail($usermail)) {
            $datum = date("YmdHis");
            $content  = $db->escape_string(strip_tags($_REQUEST["content"]));
            
            if (isset($PMF_CONF['enablevisibility'])) {
                $visibility = 'N';
            } else {
                $visibility = 'Y';
            }

            $result = $db->query("INSERT INTO ".SQLPREFIX."faqquestions (ask_username, ask_usermail, ask_rubrik, ask_content, ask_date, is_visible) VALUES ('".$user."', '".$usermail."', '".$rubrik."', '".$content."', '".$datum."', '".$visibility."')");
            
            $questionMail = "User: ".$username.", mailto:".$usermail."\n".$PMF_LANG["msgCategory"].":  ".$categories[$selected_category]["name"]."\n\n".wordwrap(stripslashes($content), 72);
            
            $headers = '';
            $db->query("SELECT ".SQLPREFIX."faquser.email FROM ".SQLPREFIX."faqcategories INNER JOIN ".SQLPREFIX."faquser ON ".SQLPREFIX."faqcategories.user_id = ".SQLPREFIX."faquser.id WHERE ".SQLPREFIX."faqcategories.id = ".$selected_category);
            while ($row = $db->fetch_object($result)) {
                $headers .= "CC: ".$row->email."\n";
            }
            
            $additional_header = array();
            $additional_header[] = 'MIME-Version: 1.0';
            $additional_header[] = 'Content-Type: text/plain; charset='. $PMF_LANG['metaCharset'];
            if (strtolower($PMF_LANG['metaCharset']) == 'utf-8') {
                $additional_header[] = 'Content-Transfer-Encoding: 8bit';
            }
            $additional_header[] = 'From: '.'<'.$IDN->encode($usermail).'>';
            $body = strip_tags($questionMail);
            $body = str_replace(array("\r\n", "\r", "\n"), $body);
            $body = str_replace(array("\r\n", "\r", "\n"), $body);
            if (strstr(PHP_OS, 'WIN') !== NULL) {
                // if windows, cr must "\r\n". if other must "\n".
                $body = str_replace("\n", "\r\n", $body);
            }
            mail($IDN->encode($PMF_CONF['adminmail']), $PMF_CONF['title'], $body, implode("\r\n", $additional_header), "-f$headers");
            
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
        
        $content  = $db->escape_string(strip_tags($_REQUEST["content"]));
        
        $tpl->templates['writeContent'] = $tpl->readTemplate('template/asksearch.tpl');
        
		$tpl->processTemplate ('writeContent', array(
			'msgQuestion' => $PMF_LANG["msgQuestion"],
            'printResult' => $printResult,
            'msgAskYourQuestion' => $PMF_LANG['msgAskYourQuestion'],
            'msgContent' => $content,
            'postUsername' => urlencode($username),
            'postUsermail' => urlencode($usermail),
            'postRubrik' => urlencode($selected_category),
            'postContent' => urlencode($content),
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
