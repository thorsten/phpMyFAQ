<?php
/**
 * The 'send an email from the contact page' page.
 *
 * @package    phpMyFAQ
 * @subpackage Freontend
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2002-09-16
 * version     SVN: $Id$ 
 * @copyright  2002-2009 phpMyFAQ Team
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

$name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$question = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);

if (!is_null($name) && !is_null($email) && !is_null($question) && IPCheck($_SERVER['REMOTE_ADDR']) && 
    checkBannedWord(htmlspecialchars($question)) && $captcha->checkCaptchaCode($code)) {

    $mail = new PMF_Mail();
    $mail->unsetFrom();
    $mail->setFrom($email, $name);
    $mail->addTo($faqconfig->get('main.administrationMail'));
    $mail->subject = 'Feedback: %sitename%';;
    $mail->message = $question;
    $result = $mail->send();
    unset($mail);

    $message = $PMF_LANG['msgMailContact'];
    
} else {
    $message = $PMF_LANG['err_sendMail'];
}

$tpl->processTemplate('writeContent', array(
                      'msgContact' => $PMF_LANG['msgContact'],
                      'Message'    => $message));

$tpl->includeTemplate('writeContent', 'index');
