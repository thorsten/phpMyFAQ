<?php
/**
* $Id: sendmail.php,v 1.12 2006-09-19 21:39:38 matteo Exp $
*
* The 'send an email from the contact page' page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
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

Tracking("sendmail_contact",0);

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (    isset($_POST["name"]) && $_POST["name"] != ''
     && isset($_POST["email"]) && checkEmail($_POST["email"])
     && isset($_POST["question"]) && $_POST["question"] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['question'])))
     && checkCaptchaCode() ) {

    list($user, $host) = explode("@", $_POST["email"]);
    $question          = htmlspecialchars($_POST["question"]);
    $sender            = $IDN->encode($_POST["email"]);
    $subject           = 'Feedback: '.$PMF_CONF['title'];
    $name              = htmlspecialchars($_POST['name']);
    if (function_exists('mb_encode_mimeheader')) {
        $name = mb_encode_mimeheader($name);
    } else {
        $name = encode_iso88591($name);
    }
    $additional_header = array();
    $additional_header[] = 'MIME-Version: 1.0';
    $additional_header[] = 'Content-Type: text/plain; charset='. $PMF_LANG['metaCharset'];
    if (strtolower( $PMF_LANG['metaCharset']) == 'utf-8') {
        $additional_header[] = 'Content-Transfer-Encoding: 8bit';
    }
    $additional_header[] = 'From: '.$name.' <'.$sender.'>';

    if (ini_get('safe_mode')) {
        mail($IDN->encode($PMF_CONF['adminmail']), $subject, $question, implode("\r\n", $additional_header));
    } else {
        mail($IDN->encode($PMF_CONF['adminmail']), $subject, $question, implode("\r\n", $additional_header), '-f'.$sender);
    }

    $tpl->processTemplate ("writeContent", array(
            "msgContact" => $PMF_LANG["msgContact"],
            "Message" => $PMF_LANG["msgMailContact"]
            ));
} else {
    $tpl->processTemplate ("writeContent", array(
                "msgContact" => $PMF_LANG["msgContact"],
                "Message" => $PMF_LANG["err_sendMail"]
                ));
}

$tpl->includeTemplate("writeContent", "index");
