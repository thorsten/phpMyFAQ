<?php
/**
 * Sends the emails to your friends
 *
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('sendmail_send2friend', 0);

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

$name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$mailfrom = PMF_Filter::filterInput(INPUT_POST, 'mailfrom', FILTER_VALIDATE_EMAIL);
$link     = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
$attached = PMF_Filter::filterInput(INPUT_POST, 'zusatz', FILTER_SANITIZE_STRIPPED);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$mailto   = PMF_Filter::filterInputArray(INPUT_POST,
    array('mailto' =>
        array('filter' => FILTER_VALIDATE_EMAIL,
              'flags'  => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE
        )
    )
);

if (!is_null($name) && !is_null($mailfrom) && is_array($mailto) && IPCheck($_SERVER['REMOTE_ADDR'])
    && checkBannedWord(PMF_String::htmlspecialchars($attached)) && $captcha->checkCaptchaCode($code)) {

    foreach ($mailto['mailto'] as $recipient) {
        $recipient = trim(strip_tags($recipient));
        if (!empty($recipient)) {
            $mail = new PMF_Mail();
            $mail->unsetFrom();
            $mail->setFrom($mailfrom, $name);
            $mail->addTo($recipient);
            $mail->subject = $PMF_LANG["msgS2FMailSubject"].$name;
            $mail->message = sprintf("%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                $faqconfig->get('main.send2friendText'),
                $PMF_LANG['msgS2FText2'],
                $link,
                $attached);
            
            // Send the email
            $result = $mail->send();
            unset($mail);
            usleep(250);
        }
    }
    
    $tpl->processTemplate('writeContent', array(
        'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
        'Message'        => $PMF_LANG['msgS2FThx']));

} else {
    if (false === IPCheck($_SERVER["REMOTE_ADDR"])) {
        $tpl->processTemplate('writeContent', array(
            'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
            'Message'        => $PMF_LANG["err_bannedIP"]));
    } else {
        $tpl->processTemplate('writeContent', array(
            'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
            'Message'        => $PMF_LANG["err_sendMail"]));
    }
}

$tpl->includeTemplate("writeContent", "index");
