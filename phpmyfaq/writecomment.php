<?php
/**
* $Id: writecomment.php,v 1.7 2006-04-09 12:22:27 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-29
* @copyright    (c) 2001-2006 phpMyFAQ Team
* 
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the 'License'); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an 'AS IS'
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);

if (isset($_GET['gen'])) {
	$captcha->showCaptchaImg();
	exit;
}

Tracking('write_comment', $_GET['id']);

$tpl->processTemplate('writeContent', array(
				      'msgCommentHeader' => $PMF_LANG['msgWriteComment'],
                      'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=savecomment',
                      'ID' => $_GET['id'],
                      'LANG' => $_GET['artlang'],
                      'writeThema' => getThema($_GET['id'], $_GET['artlang']),
                      'msgNewContentName' => $PMF_LANG['msgNewContentName'],
                      'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
                      'defaultContentMail' => getEmailAddress(),
                      'defaultContentName' => getFullUserName(),
                      'msgYourComment' => $PMF_LANG['msgYourComment'],
                      'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
                      'msgCaptcha' => $PMF_LANG['msgCaptcha'],
                      'printCaptcha' => $captcha->printCaptcha('writecomment'),
                      'setCaptchaCodeLength' => $captcha->caplength,
                      'copyright_eintrag' => unhtmlentities($PMF_CONF['copyright_eintrag'])));

$tpl->includeTemplate('writeContent', 'index');