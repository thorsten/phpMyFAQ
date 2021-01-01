<?php

/**
 * This is the page there a user can add a FAQ record translation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-11-12
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Language;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = new Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$translationLanguage = Filter::filterInput(INPUT_POST, 'translation', FILTER_SANITIZE_STRIPPED, $faqLangCode);

if (!Language::isASupportedLanguage($translationLanguage)) {
    $translationLanguage = $faqLangCode;
}

$faqSource['id'] = 'writeSourceFaqId';
$faqSource['lang'] = $translationLanguage;
$faqSource['title'] = 'writeSourceTitle';
$faqSource['content'] = 'writeSourceContent';
$faqSource['keywords'] = 'writeSourceKeywords';

try {
    $faqSession->userTracking('new_translation_entry', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$srclang = Filter::filterInput(INPUT_GET, 'srclang', FILTER_SANITIZE_STRIPPED);

if (!is_null($id) && !is_null($srclang) && Language::isASupportedLanguage($srclang)) {
    $oFaq = new Faq($faqConfig);
    $oFaq->getRecord($id);
    $faqSource = $oFaq->faqRecord;
}

$captchaHelper = new CaptchaHelper($faqConfig);

// Enable/Disable WYSIWYG editor
if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
    $template->parseBlock(
        'mainPageContent',
        'enableWysiwygEditor',
        array(
            'currentTimestamp' => $_SERVER['REQUEST_TIME'],
        )
    );
}

$template->parse(
    'mainPageContent',
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
        'writeSendAdress' => '?' . $sids . 'action=save',
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
        'tinyMCELanguage' => (Language::isASupportedTinyMCELanguage($faqLangCode) ? $faqLangCode : 'en'),
    )
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgNewTranslationHeader']
    ]
);
