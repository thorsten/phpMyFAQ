<?php
/**
 * $Id: translate.php,v 1.4 2008-05-23 13:06:07 thorstenr Exp $
 *
 * This is the page there a user can add a FAQ record.
 *
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2006-11-12
 * @copyright   (c) 2006 phpMyFAQ Team
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

$captcha = new PMF_Captcha($db, $sids, PMF_Init::$language);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit;
}

if (isset($_POST['translation']) && PMF_Init::isASupportedLanguage($_POST['translation'])) {
    $translationLanguage = strip_tags($_POST['translation']);
} else {
    $translationLanguage = $LANGCODE;
}

$faqSource['id'] = 'writeSourceFaqId';
$faqSource['lang'] = $translationLanguage;
$faqSource['title'] = 'writeSourceTitle';
$faqSource['content'] = 'writeSourceContent';
$faqSource['keywords'] = 'writeSourceKeywords';

$faqsession->userTracking('new_translation_entry', 0);

if (   isset($_GET['id']) && is_numeric($_GET['id']) && (intval($_GET['id']) > 0)
    && isset($_GET['srclang']) && PMF_Init::isASupportedLanguage($_GET['srclang'])
    ) {
    $oFaq = new PMF_Faq($db, $_GET['srclang']);
    $oFaq->getRecord((int)$_GET['id']);
    $faqSource = $oFaq->faqRecord;
}

$tpl->processTemplate('writeContent', array(
    'writeSourceFaqId'          => $faqSource['id'],
    'writeSourceTitle'          => $faqSource['title'],
    'writeSourceContent'        => $faqSource['content'],
    'writeSourceKeywords'       => $faqSource['keywords'],
    'msgNewTranslationHeader'   => $PMF_LANG['msgNewTranslationHeader'],
    'msgNewTranslationAddon'    => $PMF_LANG['msgNewTranslationAddon'],
    'msgNewTransSourcePane'     => $PMF_LANG['msgNewTransSourcePane'],
    'msgNewTranslationPane'     => $PMF_LANG['msgNewTranslationPane'],
    'writeSendAdress'           => $_SERVER['PHP_SELF'].'?'.$sids.'action=save',
    'defaultContentName'        => ($user ? $user->getUserData('display_name') : ''),
    'defaultContentMail'        => ($user ? $user->getUserData('email') : ''),
    'msgNewTranslationName'     => $PMF_LANG['msgNewTranslationName'],
    'msgNewTranslationMail'     => $PMF_LANG['msgNewTranslationMail'],
    'msgNewTranslationKeywords' => $PMF_LANG['msgNewTranslationKeywords'],
    'writeTransFaqLanguage'     => $translationLanguage,
    'captchaFieldset'           => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('translate'), $captcha->caplength),
    'msgNewTranslationSubmit'   => $PMF_LANG['msgNewTranslationSubmit'])
    );

$tpl->includeTemplate('writeContent', 'index');