<?php
/**
 * This is the page there a user can add a FAQ record.
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
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

$categoryData   = new PMF_Category_Tree_DataProvider_SingleQuery();
$categoryLayout = new PMF_Category_Layout(new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryData)));

$tpl->processTemplate(
    'writeContent', 
    array(
        'msgNewContentHeader'   => $PMF_LANG['msgNewContentHeader'],
        'msgNewContentAddon'    => $PMF_LANG['msgNewContentAddon'],
        'lang'                  => $Language->getLanguage(),
        'defaultContentMail'    => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName'    => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgNewContentName'     => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail'     => $PMF_LANG['msgNewContentMail'],
        'msgNewContentCategory' => $PMF_LANG['msgNewContentCategory'],
        'printCategoryOptions'  => $categoryLayout->renderOptions(array($inputCategory)),
        'msgNewContentTheme'    => $PMF_LANG['msgNewContentTheme'],
        'readonly'              => $readonly,
        'printQuestion'         => $question,
        'msgNewContentArticle'  => $PMF_LANG['msgNewContentArticle'],
        'msgNewContentKeywords' => $PMF_LANG['msgNewContentKeywords'],
        'msgNewContentLink'     => $PMF_LANG['msgNewContentLink'],
        'captchaFieldset'       => PMF_Helper_Captcha::getInstance()->renderFieldset(
            $PMF_LANG['msgCaptcha'], 
            $captcha->printCaptcha('add')),
        'msgNewContentSubmit'   => $PMF_LANG['msgNewContentSubmit']
    )
);

$tpl->includeTemplate('writeContent', 'index');
