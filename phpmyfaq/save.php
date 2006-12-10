<?php
/**
 * $Id: save.php,v 1.34 2006-12-10 17:52:08 matteo Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (    isset($_POST['username']) && $_POST['username'] != ''
     && isset($_POST['usermail']) && checkEmail($_POST['usermail'])
     && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
     && isset($_POST['thema']) && $_POST['thema'] != ''
     && isset($_POST['content']) && $_POST['content'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['thema'])))
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['content'])))
     && checkCaptchaCode() ) {

    Tracking("save_new_entry",0);
    $content = $db->escape_string(safeHTML(nl2br($_POST["content"])));
    $contentlink = $db->escape_string(safeHTML($_POST["contentlink"]));

    if (substr($contentlink,7) != "") {
        $content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".substr($contentlink,7)."\" target=\"_blank\">".$contentlink."</a>";
    }

    $userMail = $IDN->encode($db->escape_string($_POST["usermail"]));
    $newData = array(
        'lang'          => $LANGCODE,
        'thema'         => $db->escape_string(safeHTML($_POST['thema'])),
        'active'        => 'no',
        'content'       => $content,
        'keywords'      => $db->escape_string($_POST['keywords']),
        'author'        => $db->escape_string($_POST['username']),
        'email'         => $userMail,
        'comment'       => 'y',
        'date'          => date('YmdHis'),
        'dateStart'     => '00000000000000',
        'dateEnd'       => '99991231235959',
        'linkState'     => '',
        'linkDateCheck' => 0
        );
    $selected_category = intval($_POST['rubrik']);
    $faq->addRecord($newData, $selected_category);

    $userId = $tree->getCategoryUser($selected_category);
    $oUser = new PMF_User();
    $oUser->addDb($db);
    $oUser->getUserById($userId);

    $additional_header = array();
    $additional_header[] = 'MIME-Version: 1.0';
    $additional_header[] = 'Content-Type: text/plain; charset='. $PMF_LANG['metaCharset'];
    if (strtolower($PMF_LANG['metaCharset']) == 'utf-8') {
        $additional_header[] = 'Content-Transfer-Encoding: 8bit';
    }
    $additional_header[] = 'From: '.$userMail;
    // Let the category owner get a copy of the message
    if ($IDN->encode($PMF_CONF["adminmail"]) != $oUser->getUserData('email')) {
        $additional_header[] = "Cc: ".$oUser->getUserData('email')."\n";
    }
    $subject = unhtmlentities($PMF_CONF["title"]);
    if (function_exists('mb_encode_mimeheader')) {
        $subject = mb_encode_mimeheader($subject);
    }
    $body = unhtmlentities($PMF_LANG['msgMailCheck'])."\n".$PMF_CONF['title'].": ".PMF_Link::getSystemUri('/index.php').'/admin';
    if (ini_get('safe_mode')) {
        mail($IDN->encode($PMF_CONF["adminmail"]), $subject, $body, implode("\r\n", $additional_header));
    } else {
        mail($IDN->encode($PMF_CONF["adminmail"]), $subject, $body, implode("\r\n", $additional_header), "-f$userMail");
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
