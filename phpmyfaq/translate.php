<?php

/**
 * This is the page there a user can add a FAQ record translation.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2006-11-12
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$translationLanguage = PMF_Filter::filterInput(INPUT_POST, 'translation', FILTER_SANITIZE_STRIPPED, $LANGCODE);

if (!PMF_Language::isASupportedLanguage($translationLanguage)) {
    $translationLanguage = $LANGCODE;
}

$faqSource['id'] = 'writeSourceFaqId';
$faqSource['lang'] = $translationLanguage;
$faqSource['title'] = 'writeSourceTitle';
$faqSource['content'] = 'writeSourceContent';
$faqSource['keywords'] = 'writeSourceKeywords';

try {
    $faqsession->userTracking('new_translation_entry', 0);
} catch (PMF_Exception $e) {
    // @todo handle the exception
}

$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$srclang = PMF_Filter::filterInput(INPUT_GET, 'srclang', FILTER_SANITIZE_STRIPPED);

if (!is_null($id) && !is_null($srclang) && PMF_Language::isASupportedLanguage($srclang)) {
    $oFaq = new PMF_Faq($faqConfig);
    $oFaq->getRecord($id);
    $faqSource = $oFaq->faqRecord;
}

$captchaHelper = new PMF_Helper_Captcha($faqConfig);

// Enable/Disable WYSIWYG editor
if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
    $tpl->parseBlock(
        'writeContent',
        'enableWysiwygEditor',
        array(
            'currentTimestamp' => $_SERVER['REQUEST_TIME'],
        )
    );
}

$tpl->parse(
    'writeContent',
    array(
        'writeSourceFaqId' => $faqSource['id'],
        'writeSourceTitle' => $faqSource['title'],
        'writeSourceContent' => strip_tags($faqSource['content']),
        'writeSourceKeywords' => $faqSource['keywords'],
        'categoryId' => $categoryId,
        'msgNewTranslationHeader' => $PMF_LANG['msgNewTranslationHeader'],
        'msgNewTranslationAddon' => $PMF_LANG['msgNewTranslationAddon'],
        'msgNewTransSourcePane' => $PMF_LANG['msgNewTransSourcePane'],
        'msgNewTranslationPane' => $PMF_LANG['msgNewTranslationPane'],
        'writeSendAdress' => '?'.$sids.'action=save',
        'defaultContentName' => ($user ? $user->getUserData('display_name') : ''),
        'defaultContentMail' => ($user ? $user->getUserData('email') : ''),
        'msgNewTranslationQuestion' => $PMF_LANG['msgNewContentTheme'],
        'msgNewTranslationAnswer' => $PMF_LANG['msgNewContentArticle'],
        'msgNewTranslationName' => $PMF_LANG['msgNewTranslationName'],
        'msgNewTranslationMail' => $PMF_LANG['msgNewTranslationMail'],
        'msgNewTranslationKeywords' => $PMF_LANG['msgNewTranslationKeywords'],
        'writeTransFaqLanguage' => $translationLanguage,
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'translate', $PMF_LANG['msgCaptcha'], $auth),
        'msgNewTranslationSubmit' => $PMF_LANG['msgNewTranslationSubmit'],
        'tinyMCELanguage' => (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en'),
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgNewTranslationHeader']
    ]
);