<?php
/**
* $Id: send2friend.php,v 1.14 2007-03-29 12:34:11 thorstenr Exp $
*
* The send2friend page
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

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit;
}

Tracking('send2friend',0);

$send2friendLink = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?action=artikel&amp;cat='.(int)$_REQUEST['cat'].'&amp;id='.(int)$_REQUEST['id'].'&amp;artlang='.urlencode($_REQUEST['artlang']);

$tpl->processTemplate ('writeContent', array(
                'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
                'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=mailsend2friend',
                'msgS2FReferrer' => 'link',
                'msgS2FName' => $PMF_LANG['msgS2FName'],
                'msgS2FEMail' => $PMF_LANG['msgS2FEMail'],
                'defaultContentMail' => getEmailAddress(),
                'defaultContentName' => getFullUserName(),
                'msgS2FFriends' => $PMF_LANG['msgS2FFriends'],
                'msgS2FEMails' => $PMF_LANG['msgS2FEMails'],
                'msgS2FText' => $PMF_LANG['msgS2FText'],
                'send2friend_text' => PMF_htmlentities($PMF_CONF['send2friendText']),
                'msgS2FText2' => $PMF_LANG['msgS2FText2'],
                'send2friendLink' => $send2friendLink,
                'msgS2FMessage' => $PMF_LANG['msgS2FMessage'],
                'captchaFieldset' => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('send2friend'), $captcha->caplength),
                'msgS2FButton' => $PMF_LANG['msgS2FButton']
                ));

$tpl->includeTemplate('writeContent', 'index');
?>
