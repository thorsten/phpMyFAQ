<?php
/**
* $Id: save.php,v 1.22 2006-06-18 08:06:17 thorstenr Exp $
*
* Saves a user FAQ record and sends an email to the user
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Jürgen Kuza <kig@bluewin.ch>
* @since        2002-09-16
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
     && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
     && isset($_POST['thema']) && $_POST['thema'] != ''
     && isset($_POST['content']) && $_POST['content'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['thema'])))
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['content'])))
     && isset($_POST['captcha']) && ($captcha->validateCaptchaCode($_POST['captcha'])) ) {

    Tracking("save_new_entry",0);
	$content = $db->escape_string(safeHTML(nl2br($_POST["content"])));
    $contentlink = $db->escape_string(safeHTML($_POST["contentlink"]));

    if (substr($contentlink,7) != "") {
		$content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".substr($contentlink,7)."\" target=\"_blank\">".$contentlink."</a>";
	}

	if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
		$lang = trim(strtolower(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)));
	} else {
		$lang = "en";
	}

	$newData = array(
		'lang'		=> $lang,
		'thema' 	=> $db->escape_string(safeSQL(safeHTML($_POST['thema']))),
		'active'	=> 'no',
		'content'	=> $content,
		'keywords' 	=> $db->escape_string(safeSQL($_POST['keywords'])),
		'author' 	=> $db->escape_string(safeSQL($_POST['username'])),
    	'email' 	=> $IDN->encode($_POST['usermail']),
    	'comment'	=> 'y',
    	'date'		=> date('YmdHis')
		);
	$selected_category = intval($_POST['rubrik']);

	$faq->addRecord($newData, $selected_category);

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
    $additional_header[] = 'From: '.$usermail;
    $subject = unhtmlentities($PMF_CONF["title"]);
    if (function_exists('mb_encode_mimeheader')) {
        $subject = mb_encode_mimeheader($subject);
    }
    $body = unhtmlentities($PMF_LANG['msgMailCheck'])."\n".unhtmlentities($PMF_CONF['title']).": http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
    if (ini_get('safe_mode')) {
        mail($IDN->encode($PMF_CONF["adminmail"]), $subject, $body, implode("\r\n", $additional_header));
    } else {
        mail($IDN->encode($PMF_CONF["adminmail"]), $subject, $body, implode("\r\n", $additional_header), "-f$usermail");
    }

	$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["msgNewContentThanks"]
				));
} else {
	if (IPCheck($_SERVER["REMOTE_ADDR"]) == FALSE) {
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_bannedIP"]
				));
	} else {
		Tracking("error_save_entry", 0);
		$tpl->processTemplate ("writeContent", array(
				"msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
				"Message" => $PMF_LANG["err_SaveEntries"]
				));
	}
}

$tpl->includeTemplate("writeContent", "index");
