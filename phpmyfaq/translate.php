<?php
/**
 * This is the page there a user can add a FAQ record translation.
 *
 * PHP Version 5.2.3
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
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-11-12
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

$translationLanguage = PMF_Filter::filterInput(INPUT_POST, 'translation', FILTER_SANITIZE_STRIPPED, $LANGCODE);

if (!PMF_Language::isASupportedLanguage($translationLanguage)) {
	$translationLanguage = $LANGCODE;
}

$faqSource['id']       = 'writeSourceFaqId';
$faqSource['lang']     = $translationLanguage;
$faqSource['title']    = 'writeSourceTitle';
$faqSource['content']  = 'writeSourceContent';
$faqSource['keywords'] = 'writeSourceKeywords';

$faqsession->userTracking('new_translation_entry', 0);

$id      = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$srclang = PMF_Filter::filterInput(INPUT_GET, 'srclang', FILTER_SANITIZE_STRIPPED); 

if (!is_null($id) && !is_null($srclang) && PMF_Language::isASupportedLanguage($srclang)) {
    $oFaq = new PMF_Faq();
    $oFaq->getRecord($id);
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
    'writeSendAdress'           => '?'.$sids.'action=save',
    'defaultContentName'        => ($user ? $user->getUserData('display_name') : ''),
    'defaultContentMail'        => ($user ? $user->getUserData('email') : ''),
    'msgNewTranslationQuestion' => $PMF_LANG['msgNewContentTheme'],
    'msgNewTranslationAnswer'   => $PMF_LANG['msgNewContentArticle'],
    'msgNewTranslationName'     => $PMF_LANG['msgNewTranslationName'],
    'msgNewTranslationMail'     => $PMF_LANG['msgNewTranslationMail'],
    'msgNewTranslationKeywords' => $PMF_LANG['msgNewTranslationKeywords'],
    'writeTransFaqLanguage'     => $translationLanguage,
    'captchaFieldset'           => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('translate'), $captcha->caplength),
    'msgNewTranslationSubmit'   => $PMF_LANG['msgNewTranslationSubmit'],
    'tinyMCELanguage'           => (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en')));

$tpl->includeTemplate('writeContent', 'index');

