<?php
/**
 * Snippet for writing a comment
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
 * @since     2002-08-29
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$id      = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$artlang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRIPPED);

$faqsession->userTracking('write_comment', $id);

$tpl->processTemplate('writeContent', array(
                      'msgCommentHeader'    => $PMF_LANG['msgWriteComment'],
                      'writeSendAdress'     => '?'.$sids.'action=savecomment',
                      'ID'                  => $id,
                      'LANG'                => $artlang,
                      'writeThema'          => $faq->getRecordTitle($id),
                      'msgNewContentName'   => $PMF_LANG['msgNewContentName'],
                      'msgNewContentMail'   => $PMF_LANG['msgNewContentMail'],
                      'defaultContentMail'  => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
                      'defaultContentName'  => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
                      'msgYourComment'      => $PMF_LANG['msgYourComment'],
                      'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
                      'captchaFieldset'     => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('writecomment'), $captcha->caplength)));

$tpl->includeTemplate('writeContent', 'index');