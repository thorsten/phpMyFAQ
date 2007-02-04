<?php
/**
 * $Id: add.php,v 1.19 2007-02-04 19:27:50 thorstenr Exp $
 *
 * This is the page there a user can add a FAQ record.
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

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit;
}

Tracking('new_entry', 0);

$question = '';
$readonly = '';
if (isset($_GET['question']) && is_numeric($_GET['question'])) {
    $question_id = (int)$_GET['question'];
    $oQuestion = $faq->getQuestion($question_id);
    $question = $oQuestion['question'];
    if (strlen($question)) {
        $readonly = ' readonly="readonly"';
    }
}

if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $rubrik = intval($_GET['cat']);
    $category = array(array('category_id' => $rubrik, 'category_lang' => $LANGCODE));
} else {
    $category = array();
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
    'msgNewContentCategory' => $PMF_LANG['msgNewContentCategory'],
    'printCategoryOptions'  => $category->printCategoryOptions($category),
    'msgNewContentTheme'    => $PMF_LANG['msgNewContentTheme'],
    'readonly'              => $readonly,
    'printQuestion'         => $question,
    'msgNewContentArticle'  => $PMF_LANG['msgNewContentArticle'],
    'msgNewContentKeywords' => $PMF_LANG['msgNewContentKeywords'],
    'msgNewContentLink'     => $PMF_LANG['msgNewContentLink'],
    'captchaFieldset'       => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('add'), $captcha->caplength),
    'msgNewContentSubmit'   => $PMF_LANG['msgNewContentSubmit']));

$tpl->includeTemplate('writeContent', 'index');
