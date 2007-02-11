<?php
/**
 * $Id: mailsend2friend.php,v 1.16 2007-02-11 17:42:35 thorstenr Exp $
 *
 * Sends the emails to your friends
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2002-09-16
 * @copyright   (c) 2001-2007 phpMyFAQ Team
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

Tracking('sendmail_send2friend', 0);

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (   isset($_POST['name']) && $_POST['name'] != ''
    && isset($_POST['mailfrom']) && $_POST['mailfrom'] != ''
    && isset($_POST['mailto']) && $_POST['mailto'] != ''
    && IPCheck($_SERVER['REMOTE_ADDR'])
    && checkBannedWord(htmlspecialchars(strip_tags($_POST['zusatz'])))
    && checkCaptchaCode() ) {

    $name = $db->escape_string(strip_tags($_POST["name"]));
    $mailfrom = $db->escape_string(strip_tags($_POST["mailfrom"]));
    $link = $db->escape_string(strip_tags($_POST["link"]));
    $attached = $db->escape_string(strip_tags($_POST["zusatz"]));
    $mailto = $_POST['mailto'];

    list($user, $host) = explode("@", $mailto[0]);
    if (checkEmail($mailfrom)) {
        foreach($mailto as $mail) {
            $mail = checkEmail($db->escape_string(strip_tags($mail)));
            if ($mail != "") {
                mail(
                    $IDN->encode($mail),
                    $PMF_LANG["msgS2FMailSubject"].$name,
                    $PMF_CONF["send2friendText"]."\r\n\r\n".$PMF_LANG["msgS2FText2"]."\r\n".$link."\r\n\r\n".$attached,
                    "From: ".$IDN->encode($mailfrom)
                );
                usleep(250);
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
