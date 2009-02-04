<?php
/**
 * The 'send an email from the contact page' page.
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2002-09-16
 * version    SVN: $Id$ 
 * @copyright 2002-2009 phpMyFAQ Team
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

$faqsession->userTracking('sendmail_contact', 0);

$captcha = new PMF_Captcha($sids);

if (    isset($_POST['name']) && $_POST['name'] != ''
     && isset($_POST['email']) && checkEmail($_POST['email'])
     && isset($_POST['question']) && $_POST['question'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['question'])))
     && checkCaptchaCode() ) {

    list($user, $host) = explode('@', strip_tags($_POST['email']));
    $question          = strip_tags($_POST['question']);
    $sender            = strip_tags($_POST['email']);
    $subject           = 'Feedback: %sitename%';
    $name              = strip_tags($_POST['name']);

    $mail = new PMF_Mail();
    $mail->unsetFrom();
    $mail->setFrom($sender, $name);
    $mail->addTo($faqconfig->get('main.administrationMail'));
    $mail->subject = $subject;
    $mail->message = $question;
    $result = $mail->send();
    unset($mail);

    $tpl->processTemplate(
        'writeContent',
        array(
            'msgContact'    => $PMF_LANG['msgContact'],
            'Message'       => $PMF_LANG['msgMailContact']
        )
    );
} else {

    $tpl->processTemplate(
        'writeContent',
        array(
            'msgContact'    => $PMF_LANG['msgContact'],
            'Message'       => $PMF_LANG['err_sendMail']
        )
    );
}

$tpl->includeTemplate('writeContent', 'index');
