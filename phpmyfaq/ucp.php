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
use phpMyFAQ\Twig\TwigWrapper;
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
    $faqSession = $container->get('phpmyfaq.user.session');
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
    } catch (TwoFactorAuthException | Exception) {
        // handle exception
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
    $twigTemplate = $twig->loadTemplate('./ucp.twig');

    // Twig template variables
    $templateVars = [
        ... $templateVars,
        'headerUserControlPanel' => Translation::get(languageKey: 'headerUserControlPanel'),
        'ucpGravatarImage' => $gravatarImg,
        'msgHeaderUserData' => Translation::get(languageKey: 'headerUserControlPanel'),
        'userid' => $user->getUserId(),
        'csrf' => Token::getInstance($container->get('session'))->getTokenInput('ucp'),
        'lang' => $faqConfig->getLanguage()->getLanguage(),
        'readonly' => $user->isLocalUser() ? '' : 'readonly disabled',
        'msgRealName' => Translation::get(languageKey: 'ad_user_name'),
        'realname' => $user->getUserData('display_name'),
        'msgEmail' => Translation::get(languageKey: 'msgNewContentMail'),
        'email' => $user->getUserData('email'),
        'msgIsVisible' => Translation::get(languageKey: 'msgUserDataVisible'),
        'checked' => (int)$user->getUserData('is_visible') === 1 ? 'checked' : '',
        'msgPassword' => Translation::get(languageKey: 'ad_auth_passwd'),
        'msgConfirm' => Translation::get(languageKey: 'ad_user_confirm'),
        'msgSave' => Translation::get(languageKey: 'msgSave'),
        'msgCancel' => Translation::get(languageKey: 'msgCancel'),
        'twofactor_enabled' => (bool)$user->getUserData('twofactor_enabled'),
        'msgTwofactorEnabled' => Translation::get(languageKey: 'msgTwofactorEnabled'),
        'msgTwofactorConfig' => Translation::get(languageKey: 'msgTwofactorConfig'),
        'msgTwofactorConfigModelTitle' => Translation::get(languageKey: 'msgTwofactorConfigModelTitle'),
        'twofactor_secret' => $secret ?? '',
        'qr_code_secret' => $qrCode,
        'qr_code_secret_alt' => Translation::get(languageKey: 'qr_code_secret_alt'),
        'msgTwofactorNewSecret' => Translation::get(languageKey: 'msgTwofactorNewSecret'),
        'msgWarning' => Translation::get(languageKey: 'msgWarning'),
        'ad_gen_yes' => Translation::get(languageKey: 'ad_gen_yes'),
        'ad_gen_no' => Translation::get(languageKey: 'ad_gen_no'),
        'msgConfirmTwofactorConfig' => Translation::get(languageKey: 'msgConfirmTwofactorConfig'),
        'csrfTokenRemoveTwofactor' => Token::getInstance($container->get('session'))->getTokenString('remove-twofactor'),
        'msgGravatarNotConnected' => Translation::get(languageKey: 'msgGravatarNotConnected'),
        'webauthnSupportEnabled' => $faqConfig->get('security.enableWebAuthnSupport'),
        'csrfExportUserData' => Token::getInstance($container->get('session'))->getTokenInput('export-userdata'),
        'exportUserDataUrl' => 'api/user/data/export',
        'msgDownloadYourData' => Translation::get(languageKey: 'msgDownloadYourData'),
        'msgDataExportDescription' => Translation::get(languageKey: 'msgDataExportDescription'),
        'msgDownload' => Translation::get(languageKey: 'msgDownload'),
    ];

    return $templateVars;
}

// Redirect to log in
$response = new RedirectResponse($faqConfig->getDefaultUrl());
$response->send();
