<?php

/**
 * The login form.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2013-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

$faqConfig = Configuration::getConfigurationInstance();

if (isset($error) && 0 < strlen((string) $error)) {
    $errorMessage = $error;
} else {
    $errorMessage = '';
}

$request = Request::createFromGlobals();

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/login.twig');

$templateVars = [
    'isSecure' => $request->isSecure() || !$faqConfig->get('security.useSslForLogins'),
    'isError' => isset($error) && 0 < strlen((string) $error),
    'errorMessage' => $errorMessage,
    'loginMessage' => Translation::get('ad_auth_insert'),
    'isLogout' => $request->query->get('action') === 'logout',
    'logoutMessage' => Translation::get('ad_logout'),
    'loginUrl' => $faqConfig->getDefaultUrl() . 'admin/index.php',
    'redirectAction' => Strings::htmlentities($request->query->get('action') ?? '') ,
    'msgUsername' => Translation::get('ad_auth_user'),
    'msgPassword' => Translation::get('ad_auth_passwd'),
    'msgRememberMe' => Translation::get('rememberMe'),
    'msgLostPassword' => Translation::get('lostPassword'),
    'msgLoginUser' => Translation::get('msgLoginUser'),
    'hasRegistrationEnabled' => $faqConfig->get('security.enableRegistration'),
    'msgRegistration' => Translation::get('msgRegistration'),
    'hasSignInWithMicrosoftActive' => $faqConfig->isSignInWithMicrosoftActive(),
    'msgSignInWithMicrosoft' => Translation::get('msgSignInWithMicrosoft'),
    'secureUrl' => sprintf('https://%s%s', $request->getHost(), $request->getRequestUri()),
    'msgNotSecure' => Translation::get('msgSecureSwitch'),
    'isWebAuthnEnabled' => $faqConfig->get('security.enableWebAuthnSupport'),
];

echo $template->render($templateVars);
