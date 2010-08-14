<?php
/**
 * This module is for user registration.
 *
 * User may register but registration is only knowlegded for registration.
 * Admin will receive email and has to activate this user.
 *
 * @package    phpMyFAQ
 * @subpackage Frontend
 * @author     Elger Thiele <elger@phpmyfaq.de>
 * @since      2008-01-25
 * @version    SVN: $Id$
 * @copyright  2008-2009 phpMyFAQ Team
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

// Settings
$selectSize        = 10;
$defaultUserAction = 'list';
$defaultUserStatus = 'blocked';
$loginMinLength    = 4;

$errorMessages = array(
    'addUser_password'           => $PMF_LANG['ad_user_error_password'],
    'addUser_passwordsDontMatch' => $PMF_LANG['ad_user_error_passwordsDontMatch'],
    'addUser_loginExists'        => $PMF_LANG["ad_adus_exerr"],
    'addUser_loginInvalid'       => $PMF_LANG['ad_user_error_loginInvalid'],
    'addUser_noEmail'            => $PMF_LANG['ad_user_error_noEmail'],
    'addUser_noRealName'         => $PMF_LANG['ad_user_error_noRealName'],
    'delUser'                    => $PMF_LANG['ad_user_error_delete'],
    'delUser_noId'               => $PMF_LANG['ad_user_error_noId'],
    'delUser_protectedAccount'   => $PMF_LANG['ad_user_error_protectedAccount'],
    'updateUser'                 => $PMF_LANG['ad_msg_mysqlerr'],
    'updateUser_noId'            => $PMF_LANG['ad_user_error_noId'],
    'updateRights'               => $PMF_LANG['ad_msg_mysqlerr'],
    'updateRights_noId'          => $PMF_LANG['ad_user_error_noId']);

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

$loginname = PMF_Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING);
$lastname  = PMF_Filter::filterInput(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
$email     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$code      = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);

if (!$captcha->checkCaptchaCode($code)) {
    $captchaError = $PMF_LANG['captchaError'];
}

if (!is_null($loginname) && !is_null($lastname) && !is_null($email) && !isset($captchaError)) {

    $user     = new PMF_User();
    $message  = '';
    $messages = array();

    // check input data
    $user_name     = $loginname;
    $user_realname = $lastname;
    $user_password = '';
    $user_email    = $email;

    // check login name
    $user->setLoginMinLength($loginMinLength);
    if (!$user->isValidLogin($user_name)) {
        $user_name  = '';
        $messages[] = $errorMessages['addUser_loginInvalid'];
    }
    if ($user->getUserByLogin($user_name)) {
        $user_name  = '';
        $messages[] = $errorMessages['addUser_loginExists'];
    }
    
    // check realname
    if ($user_realname == '') {
        $user_realname = '';
        $messages[]    = $errorMessages['addUser_noRealName'];
    }
    
    // ok, let's go
    if (count($messages) == 0) {
        // Create user account (login and password)
        // Note: password be automatically generated
        //       and sent by email as soon if admin switch user to "active"
        if (!$user->createUser($user_name, '')) {
            $messages[] = $user->error();
        } else {
            // set user data (realname, email)
            $user->userdata->set(
                array('display_name', 'email'),
                array($user_realname, $user_email));
            // set user status
            $user->setStatus($defaultUserStatus);

            $text = sprintf("New user has been registrated:\n\nUsername: %s\nLoginname: %s\n\n" .
                            "To activate this user do please use the administration interface.",
                            $lastname,
                            $loginname);
            
            $mail = new PMF_Mail();
            $mail->unsetFrom();
            $mail->setFrom($user_email);
            $mail->addTo($faqconfig->get('main.administrationMail'));
            $mail->subject = PMF_Utils::resolveMarkers($PMF_LANG['emailRegSubject']);
            $mail->message = $text;
            $result = $mail->send();
            unset($mail);
            
            header("Location: index.php?action=thankyou");
            exit;
        }
    }
    // no errors, show list
    if (count($messages) == 0) {
        $userAction = $defaultUserAction;

    // display error messages and show form again
    } else {
        $tpl->processTemplate('writeContent', array(
            'regErrors'               => sprintf("<strong>%s</strong> <br /> - %s <br /><br />", $PMF_LANG['msgRegError'], implode("<br />- ", $messages)),
            'msgUserData'             => $PMF_LANG['msgUserData'],
            'login_errorRegistration' => (!is_null($loginname)) ? $PMF_LANG['errorRegistration'] : '',
            'name_errorRegistration'  => (!is_null($lastname)) ? $PMF_LANG['errorRegistration'] : '',
            'email_errorRegistration' => (!is_null($email)) ? $PMF_LANG['errorRegistration'] : '',
            'loginname'               => $PMF_LANG["ad_user_loginname"],
            'lastname'                => $PMF_LANG["ad_user_realname"],
            'email'                   => $PMF_LANG["ad_entry_email"],
            'loginname_value'         => $loginname,
            'lastname_value'          => $lastname,
            'email_value'             => $email,
            'submitRegister'          => $PMF_LANG['submitRegister'],
            'captchaFieldset'         => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('add'), $captcha->caplength, isset($captchaError) ? $captchaError : '')));

        $tpl->includeTemplate('writeContent', 'index');
    }

} else {

    $tpl->processTemplate('writeContent', array(
        'regErrors'               => '',
        'msgUserData'             => $PMF_LANG['msgUserData'],
        'login_errorRegistration' => '',
        'name_errorRegistration'  => '',
        'email_errorRegistration' => '',
        'loginname'               => $PMF_LANG["ad_user_loginname"],
        'lastname'                => $PMF_LANG["ad_user_realname"],
        'email'                   => $PMF_LANG["ad_entry_email"],
        'loginname_value'         => $loginname,
        'lastname_value'          => $lastname,
        'email_value'             => $email,
        'submitRegister'          => $PMF_LANG['submitRegister'],
        'captchaFieldset'         => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('add'), $captcha->caplength, isset($captchaError) ? $captchaError : '')));

    $tpl->includeTemplate('writeContent', 'index');
}
