<?php
/**
 * Page for adding new questions
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
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
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

$faqsession->userTracking('ask_question', 0);

$category->buildTree();

$categoryId = PMF_Filter::filterInput(INPUT_GET, 'category_id', FILTER_VALIDATE_INT, 0);

$helper = PMF_Helper_Category::getInstance();
$helper->setCategory($category);

$tpl->processTemplate('writeContent', array(
    'msgQuestion'          => $PMF_LANG['msgQuestion'],
    'msgNewQuestion'       => $PMF_LANG['msgNewQuestion'],
    'lang'                 => $Language->getLanguage(),
    'msgNewContentName'    => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'    => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'   => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
    'defaultContentName'   => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
    'msgAskCategory'       => $PMF_LANG['msgAskCategory'],
    'printCategoryOptions' => $helper->renderCategoryOptions($categoryId),
    'msgAskYourQuestion'   => $PMF_LANG['msgAskYourQuestion'],
    'captchaFieldset'      => PMF_Helper_Captcha::getInstance()->renderFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('ask')),
    'msgNewContentSubmit'  => $PMF_LANG['msgNewContentSubmit']));

$tpl->includeTemplate('writeContent', 'index');