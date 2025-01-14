<?php

/**
 * User Control Panel.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-01-12
 */

use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\TwoFactor;
use RobThree\Auth\TwoFactorAuthException;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

if ($user->isLoggedIn()) {
    $faqSession = $container->get('phpmyfaq.session');
    $faqSession->setCurrentUser($user);
    $faqSession->userTracking('user_control_panel', $user->getUserId());

    if ($faqConfig->get('main.enableGravatarSupport')) {
        $gravatar = $container->get('phpmyfaq.services.gravatar');
        $gravatarImg = sprintf(
            '<a target="_blank" href="https://www.gravatar.com">%s</a>',
            $gravatar->getImage(
                $user->getUserData('email'),
                ['class' => 'img-responsive rounded-circle', 'size' => 125]
            )
        );
    } else {
        $gravatarImg = '';
    }

    $qrCode = '';
    try {
        $twoFactor = new TwoFactor($faqConfig, $user);
        $secret = $twoFactor->getSecret($user);
        if ('' === $secret || is_null($secret)) {
            try {
                $secret = $twoFactor->generateSecret();
            } catch (TwoFactorAuthException $e) {
                $faqConfig->getLogger()->error('Cannot generate 2FA secret: ' . $e->getMessage());
            }
            $twoFactor->saveSecret($secret);
        }
        $qrCode = $twoFactor->getQrCode($secret);
    } catch (TwoFactorAuthException | Exception $e) {
        // handle exception
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
    $twigTemplate = $twig->loadTemplate('./ucp.twig');

    // Twig template variables
    $templateVars = [
        ... $templateVars,
        'headerUserControlPanel' => Translation::get('headerUserControlPanel'),
        'ucpGravatarImage' => $gravatarImg,
        'msgHeaderUserData' => Translation::get('headerUserControlPanel'),
        'userid' => $user->getUserId(),
        'csrf' => Token::getInstance()->getTokenInput('ucp'),
        'lang' => $faqConfig->getLanguage()->getLanguage(),
        'readonly' => $user->isLocalUser() ? '' : 'readonly disabled',
        'msgRealName' => Translation::get('ad_user_name'),
        'realname' => Strings::htmlentities($user->getUserData('display_name')),
        'msgEmail' => Translation::get('msgNewContentMail'),
        'email' => Strings::htmlentities($user->getUserData('email')),
        'msgIsVisible' => Translation::get('msgUserDataVisible'),
        'checked' => (int)$user->getUserData('is_visible') === 1 ? 'checked' : '',
        'msgPassword' => Translation::get('ad_auth_passwd'),
        'msgConfirm' => Translation::get('ad_user_confirm'),
        'msgSave' => Translation::get('msgSave'),
        'msgCancel' => Translation::get('msgCancel'),
        'twofactor_enabled' => (bool)$user->getUserData('twofactor_enabled'),
        'msgTwofactorEnabled' => Translation::get('msgTwofactorEnabled'),
        'msgTwofactorConfig' => Translation::get('msgTwofactorConfig'),
        'msgTwofactorConfigModelTitle' => Translation::get('msgTwofactorConfigModelTitle'),
        'twofactor_secret' => $secret ?? '',
        'qr_code_secret' => $qrCode,
        'qr_code_secret_alt' => Translation::get('qr_code_secret_alt'),
        'msgTwofactorNewSecret' => Translation::get('msgTwofactorNewSecret'),
        'msgWarning' => Translation::get('msgWarning'),
        'ad_gen_yes' => Translation::get('ad_gen_yes'),
        'ad_gen_no' => Translation::get('ad_gen_no'),
        'msgConfirmTwofactorConfig' => Translation::get('msgConfirmTwofactorConfig'),
        'csrfTokenRemoveTwofactor' => Token::getInstance()->getTokenString('remove-twofactor'),
        'msgGravatarNotConnected' => Translation::get('msgGravatarNotConnected'),
        'webauthnSupportEnabled' => $faqConfig->get('security.enableWebAuthnSupport')
    ];

    return $templateVars;
} else {
    // Redirect to log in
    $response = new RedirectResponse($faqConfig->getDefaultUrl());
    $response->send();
}
