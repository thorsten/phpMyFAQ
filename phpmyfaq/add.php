<?php
/**
 * This is the page there a user can add a FAQ record.
 *
 * @package   Frontend
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2002-09-16
 * @copyright 2002-2009 phpMyFAQ Team
 * @version   SVN: $Id$
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

$captcha = new PMF_Captcha($sids);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit;
}

$faqsession->userTracking('new_entry', 0);

$question = '';
$readonly = '';
if (isset($_GET['question']) && is_numeric($_GET['question'])) {
    $question_id = (int)$_GET['question'];
    $oQuestion   = $faq->getQuestion($question_id);
    $question    = $oQuestion['question'];
    if (strlen($question)) {
        $readonly = ' readonly="readonly"';
    }
}

if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $categories = array(array(
        'category_id'   => (int)$_GET['cat'],
        'category_lang' => $LANGCODE));
} else {
    $categories = array();
}

$category->buildTree();

$tpl->processTemplate('writeContent', array(
    'msgNewContentHeader'   => $PMF_LANG['msgNewContentHeader'],
    'msgNewContentAddon'    => $PMF_LANG['msgNewContentAddon'],
    'writeSendAdress'       => $_SERVER['PHP_SELF'].'?'.$sids.'action=save',
    'defaultContentMail'    => getEmailAddress(),
    'defaultContentName'    => getFullUserName(),
    'msgNewContentName'     => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'     => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'    => getEmailAddress(),
    'defaultContentName'    => getFullUserName(),
    'msgNewContentCategory' => $PMF_LANG['msgNewContentCategory'],
    'printCategoryOptions'  => $category->printCategoryOptions($categories),
    'msgNewContentTheme'    => $PMF_LANG['msgNewContentTheme'],
    'readonly'              => $readonly,
    'printQuestion'         => $question,
    'msgNewContentArticle'  => $PMF_LANG['msgNewContentArticle'],
    'msgNewContentKeywords' => $PMF_LANG['msgNewContentKeywords'],
    'msgNewContentLink'     => $PMF_LANG['msgNewContentLink'],
    'captchaFieldset'       => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('add'), $captcha->caplength),
    'msgNewContentSubmit'   => $PMF_LANG['msgNewContentSubmit']));

$tpl->includeTemplate('writeContent', 'index');
