<?php

/**
 * This module is for user registration.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Elger Thiele <elger@phpmyfaq.de>
 * @copyright 2008-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-25
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Configuration;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if (!$faqConfig->get('security.enableRegistration')) {
    $redirect = new RedirectResponse($faqSystem->getSystemUri($faqConfig));
    $redirect->send();
}

$faqSession->userTracking('registration', 0);

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$twigTemplate = $twig->loadTemplate('./register.twig');

// Twig template variables
$templateVars = [
    'pageHeader' => Translation::get('msgRegistration'),
    'msgRegistration' => Translation::get('msgRegistration'),
    'msgRegistrationCredentials' => Translation::get('msgRegistrationCredentials'),
    'msgRegistrationNote' => Translation::get('msgRegistrationNote'),
    'lang' => $faqLangCode,
    'loginname' => Translation::get('ad_user_loginname'),
    'realname' => Translation::get('ad_user_realname'),
    'email' => Translation::get('ad_entry_email'),
    'is_visible' => Translation::get('ad_user_data_is_visible'),
    'submitRegister' => Translation::get('submitRegister'),
    'captchaFieldset' =>
        $captchaHelper->renderCaptcha($captcha, 'register', Translation::get('msgCaptcha'), $user->isLoggedIn()),
];

$template->addRenderedTwigOutput(
    'mainPageContent',
    $twigTemplate->render($templateVars)
);
