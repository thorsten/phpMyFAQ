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

use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

if (!$faqConfig->get('security.enableRegistration')) {
    $redirect = new RedirectResponse($faqConfig->getDefaultUrl());
    $redirect->send();
}

$faqSession = $container->get('phpmyfaq.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('registration', 0);

$captcha = $container->get('phpmyfaq.captcha');
$captcha->setSessionId($sids);

$captchaHelper = $container->get('phpmyfaq.captcha.helper.captcha_helper');

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./register.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get('msgRegistration'), $faqConfig->getTitle()),
    'lang' => $faqLangCode,
    'isWebAuthnEnabled' => $faqConfig->get('security.enableWebAuthnSupport'),
    'captchaFieldset' => $captchaHelper->renderCaptcha(
        $captcha,
        'register',
        Translation::get('msgCaptcha'),
        $user->isLoggedIn()
    ),
];

return $templateVars;
