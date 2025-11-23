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
 * @copyright 2012-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-01-12
 */

use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
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
    $faqSession = $container->get('phpmyfaq.user.session');
    $faqSession->setCurrentUser($user);
    $faqSession->userTracking('user_control_panel', $user->getUserId());

    if ($faqConfig->get('main.enableGravatarSupport')) {
        $gravatar = $container->get('phpmyfaq.services.gravatar');
        $gravatarImg = sprintf('<a target="_blank" href="https://www.gravatar.com">%s</a>', $gravatar->getImage(
            $user->getUserData('email'),
            ['class' => 'img-responsive rounded-circle', 'size' => 125],
        ));
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
    } catch (TwoFactorAuthException|Exception) {
        // handle exception
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
    $twigTemplate = $twig->loadTemplate('./ucp.twig');

    // Twig template variables
    $templateVars = [
        ...$templateVars,
        'headerUserControlPanel' => Translation::get(key: 'headerUserControlPanel'),
        'ucpGravatarImage' => $gravatarImg,
        'msgHeaderUserData' => Translation::get(key: 'headerUserControlPanel'),
        'userid' => $user->getUserId(),
        'csrf' => Token::getInstance($container->get('session'))->getTokenInput('ucp'),
        'lang' => $faqConfig->getLanguage()->getLanguage(),
        'readonly' => $user->isLocalUser() ? '' : 'readonly disabled',
        'msgRealName' => Translation::get(key: 'ad_user_name'),
        'realname' => $user->getUserData('display_name'),
        'msgEmail' => Translation::get(key: 'msgNewContentMail'),
        'email' => $user->getUserData('email'),
        'msgIsVisible' => Translation::get(key: 'msgUserDataVisible'),
        'checked' => (int) $user->getUserData('is_visible') === 1 ? 'checked' : '',
        'msgPassword' => Translation::get(key: 'ad_auth_passwd'),
        'msgConfirm' => Translation::get(key: 'ad_user_confirm'),
        'msgSave' => Translation::get(key: 'msgSave'),
        'msgCancel' => Translation::get(key: 'msgCancel'),
        'twofactor_enabled' => (bool) $user->getUserData('twofactor_enabled'),
        'msgTwofactorEnabled' => Translation::get(key: 'msgTwofactorEnabled'),
        'msgTwofactorConfig' => Translation::get(key: 'msgTwofactorConfig'),
        'msgTwofactorConfigModelTitle' => Translation::get(key: 'msgTwofactorConfigModelTitle'),
        'twofactor_secret' => $secret ?? '',
        'qr_code_secret' => $qrCode,
        'qr_code_secret_alt' => Translation::get(key: 'qr_code_secret_alt'),
        'msgTwofactorNewSecret' => Translation::get(key: 'msgTwofactorNewSecret'),
        'msgWarning' => Translation::get(key: 'msgWarning'),
        'ad_gen_yes' => Translation::get(key: 'ad_gen_yes'),
        'ad_gen_no' => Translation::get(key: 'ad_gen_no'),
        'msgConfirmTwofactorConfig' => Translation::get(key: 'msgConfirmTwofactorConfig'),
        'csrfTokenRemoveTwofactor' => Token::getInstance($container->get('session'))->getTokenString(
            'remove-twofactor',
        ),
        'msgGravatarNotConnected' => Translation::get(key: 'msgGravatarNotConnected'),
        'webauthnSupportEnabled' => $faqConfig->get('security.enableWebAuthnSupport'),
        'csrfExportUserData' => Token::getInstance($container->get('session'))->getTokenInput('export-userdata'),
        'exportUserDataUrl' => 'api/user/data/export',
        'msgDownloadYourData' => Translation::get(key: 'msgDownloadYourData'),
        'msgDataExportDescription' => Translation::get(key: 'msgDataExportDescription'),
        'msgDownload' => Translation::get(key: 'msgDownload'),
    ];

    return $templateVars;
}

// Redirect to log in
$response = new RedirectResponse($faqConfig->getDefaultUrl());
$response->send();
