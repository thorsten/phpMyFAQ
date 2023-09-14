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
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-01-12
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use RobThree\Auth\TwoFactorAuthException;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();

if ($user->isLoggedIn()) {
    try {
        $faqSession->userTracking('user_control_panel', $user->getUserId());
    } catch (Exception $exception) {
        $faqConfig->getLogger()->error('Tracking of user control panel', ['exception' => $exception->getMessage()]);
    }

    if ($faqConfig->get('main.enableGravatarSupport')) {
        $gravatar = new Gravatar();
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

    $tfa = new TwoFactor($faqConfig);
    $secret = $tfa->getSecret(CurrentUser::getFromSession($faqConfig));
    if (is_null($secret)) {
        try {
            $secret = $tfa->generateSecret();
        } catch (TwoFactorAuthException $e) {
            $faqConfig->getLogger()->error('Cannot generate 2FA secret: ' . $e->getMessage());
        }
        $tfa->saveSecret($secret);
    }

    $template->parse(
        'mainPageContent',
        [
            'headerUserControlPanel' => Translation::get('headerUserControlPanel'),
            'ucpGravatarImage' => $gravatarImg,
            'userid' => $user->getUserId(),
            'csrf' => Token::getInstance()->getTokenInput('ucp'),
            'lang' => $Language->getLanguage(),
            'readonly' => $user->isLocalUser() ? '' : 'readonly disabled',
            'msgRealName' => Translation::get('ad_user_name'),
            'realname' => Strings::htmlentities($user->getUserData('display_name')),
            'msgEmail' => Translation::get('msgNewContentMail'),
            'email' => $user->getUserData('email'),
            'msgIsVisible' => Translation::get('ad_user_data_is_visible'),
            'checked' => (int)$user->getUserData('is_visible') === 1 ? 'checked' : '',
            'msgPassword' => Translation::get('ad_auth_passwd'),
            'msgConfirm' => Translation::get('ad_user_confirm'),
            'msgSave' => Translation::get('msgSave'),
            'msgCancel' => Translation::get('msgCancel'),
            'checked_twofactor_enabled' => (int)$user->getUserData('twofactor_enabled') === 1 ? 'checked' : '',
            'msgTwofactorEnabled' => Translation::get('msgTwofactorEnabled'),
            'msgTwofactorConfig' => Translation::get('msgTwofactorConfig'),
            'msgTwofactorConfigModelTitle' => Translation::get('msgTwofactorConfigModelTitle'),
            'twofactor_secret' => $secret,
            'qr_code_secret' => $tfa->getQrCode($secret),
            'qr_code_secret_alt' => Translation::get('qr_code_secret_alt'),
            'msgTwofactorNewSecret' => Translation::get('msgTwofactorNewSecret'),
        ]
    );

    $template->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => Translation::get('headerUserControlPanel')
        ]
    );
} else {
    // Redirect to login page
    $redirect = new RedirectResponse($faqConfig->getDefaultUrl());
    $redirect->send();
}
