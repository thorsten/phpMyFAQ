<?php

/**
 * Contact page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Session;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}


$faqConfig = $container->get('phpmyfaq.configuration');
$user = CurrentUser::getCurrentUser($faqConfig);
$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);

$faqSession->userTracking('contact', 0);

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

if ($faqConfig->get('layout.contactInformationHTML')) {
    $contactText = html_entity_decode((string) $faqConfig->get('main.contactInformation'));
} else {
    $contactText = nl2br($faqConfig->get('main.contactInformation'));
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/' . TwigWrapper::getTemplateSetName());
$twigTemplate = $twig->loadTemplate('./contact.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get('msgContact'), $faqConfig->getTitle()),
    'msgContactOwnText' => $contactText,
    'privacyURL' => $faqConfig->get('main.privacyURL'),
    'lang' => $Language->getLanguage(),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' => ($user->getUserId() > 0) ? $user->getUserData('display_name') : '',
    'version' => $faqConfig->getVersion(),
    'captchaFieldset' =>
        $captchaHelper->renderCaptcha($captcha, 'contact', Translation::get('msgCaptcha'), $user->isLoggedIn()),
];

return $templateVars;
