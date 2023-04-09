<?php

/**
 * This is the page there a user can add a FAQ record translation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-11-12
 */

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Captcha\Helper\BuiltinCaptchaHelper;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = new BuiltinCaptcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$translationLanguage = Filter::filterInput(INPUT_POST, 'translation', FILTER_SANITIZE_SPECIAL_CHARS, $faqLangCode);

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
} catch (Exception) {
    // @todo handle the exception
}

$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$srclang = Filter::filterInput(INPUT_GET, 'srclang', FILTER_SANITIZE_SPECIAL_CHARS);

if (!is_null($id) && !is_null($srclang) && Language::isASupportedLanguage($srclang)) {
    $oFaq = new Faq($faqConfig);
    $oFaq->getRecord($id);
    $faqSource = $oFaq->faqRecord;
}

$captchaHelper = new BuiltinCaptchaHelper($faqConfig);

// Enable/Disable WYSIWYG editor
if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
    $template->parseBlock(
        'mainPageContent',
        'enableWysiwygEditor',
        [
            'currentTimestamp' => $_SERVER['REQUEST_TIME'],
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'writeSourceFaqId' => $faqSource['id'],
        'writeSourceTitle' => $faqSource['title'],
        'writeSourceContent' => strip_tags((string) $faqSource['content']),
        'writeSourceKeywords' => $faqSource['keywords'],
        'categoryId' => $categoryId,
        'msgNewTranslationHeader' => Translation::get('msgNewTranslationHeader'),
        'msgNewTranslationAddon' => Translation::get('msgNewTranslationAddon'),
        'msgNewTransSourcePane' => Translation::get('msgNewTransSourcePane'),
        'msgNewTranslationPane' => Translation::get('msgNewTranslationPane'),
        'formActionUrl' => '?' . $sids . 'action=save',
        'defaultContentName' => ($user ? $user->getUserData('display_name') : ''),
        'defaultContentMail' => ($user ? $user->getUserData('email') : ''),
        'msgNewTranslationQuestion' => Translation::get('msgNewContentTheme'),
        'msgNewTranslationAnswer' => Translation::get('msgNewContentArticle'),
        'msgNewTranslationName' => Translation::get('msgNewTranslationName'),
        'msgNewTranslationMail' => Translation::get('msgNewTranslationMail'),
        'msgNewTranslationKeywords' => Translation::get('msgNewTranslationKeywords'),
        'writeTransFaqLanguage' => $translationLanguage,
        'captchaFieldset' => $captchaHelper->renderCaptcha(
            $captcha,
            'translate',
            Translation::get('msgCaptcha'),
            $auth
        ),
        'msgNewTranslationSubmit' => Translation::get('msgNewTranslationSubmit'),
        'tinyMCELanguage' => 'en']
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => Translation::get('msgNewTranslationHeader')
    ]
);
