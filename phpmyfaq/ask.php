<?php
/**
* $Id: ask.php,v 1.9 2006-06-12 22:09:26 matteo Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-17
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

if (isset($_GET['gen'])) {
	$captcha->showCaptchaImg();
	exit;
}

Tracking('ask_question', 0);

$tree->buildTree();

$tpl->processTemplate('writeContent', array(
				      'msgQuestion' => $PMF_LANG['msgQuestion'],
				      'msgNewQuestion' => $PMF_LANG['msgNewQuestion'],
                      'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=savequestion',
                      'msgNewContentName' => $PMF_LANG['msgNewContentName'],
                      'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
				      'defaultContentMail' => getEmailAddress(),
				      'defaultContentName' => getFullUserName(),
                      'msgAskCategory' => $PMF_LANG['msgAskCategory'],
                      'printCategoryOptions' => $tree->printCategoryOptions(),
                      'msgAskYourQuestion' => $PMF_LANG['msgAskYourQuestion'],
                      'captchaFieldset' => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('ask'), $captcha->caplength),
                      'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit']));

$tpl->includeTemplate('writeContent', 'index');
?>
