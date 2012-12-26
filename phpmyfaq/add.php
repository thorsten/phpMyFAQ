<?php
/**
 * This is the page there a user can add a FAQ record.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($faqConfig);
$captcha->setSessionId($sids);

if (! is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$faqsession->userTracking('new_entry', 0);

// Get possible user input
$inputQuestion = PMF_Filter::filterInput(INPUT_GET, 'question', FILTER_VALIDATE_INT);
$inputCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);

$question = $readonly = '';
if (!is_null($inputQuestion)) {
    $oQuestion = $faq->getQuestion($inputQuestion);
    $question  = $oQuestion['question'];
    if (PMF_String::strlen($question)) {
        $readonly = ' readonly="readonly"';
    }
}

$category->buildTree();

$categoryHelper = new PMF_Helper_Category();
$categoryHelper->setCategory($category);

$captchaHelper = new PMF_Helper_Captcha($faqConfig);

// Enable/Disable WYSIWYG editor
if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
    $tpl->parseBlock(
        'writeContent',
        'enableWysiwygEditor',
        array(
            'currentTimestamp' => $_SERVER['REQUEST_TIME']
        )
    );
}

$tpl->parse(
    'writeContent', 
    array(
        'msgNewContentHeader'   => $PMF_LANG['msgNewContentHeader'],
        'msgNewContentAddon'    => $PMF_LANG['msgNewContentAddon'],
        'lang'                  => $Language->getLanguage(),
        'openQuestionID'        => $inputQuestion,
        'defaultContentMail'    => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName'    => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgNewContentName'     => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail'     => $PMF_LANG['msgNewContentMail'],
        'msgNewContentCategory' => $PMF_LANG['msgNewContentCategory'],
        'printCategoryOptions'  => $categoryHelper->renderOptions($inputCategory),
        'msgNewContentTheme'    => $PMF_LANG['msgNewContentTheme'],
        'readonly'              => $readonly,
        'printQuestion'         => $question,
        'msgNewContentArticle'  => $PMF_LANG['msgNewContentArticle'],
        'msgNewContentKeywords' => $PMF_LANG['msgNewContentKeywords'],
        'msgNewContentLink'     => $PMF_LANG['msgNewContentLink'],
        'captchaFieldset'       => $captchaHelper->renderCaptcha($captcha, 'add', $PMF_LANG['msgCaptcha'], $auth),
        'msgNewContentSubmit'   => $PMF_LANG['msgNewContentSubmit']
    )
);

$tpl->merge('writeContent', 'index');
