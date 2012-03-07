<?php
/**
 * Page for adding new questions
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($faqConfig);
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

$tpl->parse(
    'writeContent',
    array(
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
        'captchaFieldset'      => PMF_Helper_Captcha::getInstance()->renderCaptcha(
            $captcha,
            'ask',
            $PMF_LANG['msgCaptcha']
        ),
        'msgNewContentSubmit'  => $PMF_LANG['msgNewContentSubmit']
    )
);

$tpl->merge('writeContent', 'index');
