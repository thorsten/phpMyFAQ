<?php

/**
 * Class for Two-Factor Authentication (2FA).
 *
 * This class handles all operations around creating, saving and getting the secret
 * for a CurrentUser for two-factor-authentication. It also validates given tokens in
 * comparison to a given secret and returns a QR-code for transmitting a secret to
 * the authenticator-app.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

class TwoFactor
{
    private readonly TwoFactorAuth $twoFactorAuth;
    
    private readonly Configuration $config;

    public function __construct($faqConfig)
    {
        $this->twoFactorAuth = new TwoFactorAuth();
        $this->config = $faqConfig;
    }

    /**
     * Generates and returns a new secret without saving
     * @return string
     * @throws TwoFactorAuthException
     */
    public function generateSecret(): string
    {
        return $this->twoFactorAuth->createSecret();
    }

    /**
     * Saves a given secret to the current user from the session.
     *
     * @param string $secret
     * @return true
     */
    public function saveSecret(string $secret): bool
    {
        $user = CurrentUser::getFromSession($this->config);
        $user->setUserData(['secret' => $secret]);
        return true;
    }

    /**
     * Returns the secret of the current user
     *
     * @param CurrentUser $user
     * @return string
     */
    public function getSecret(CurrentUser $user): string
    {
        return $user->getUserData('secret');
    }

    /**
     * Validates a given token. Returns true if the token is correct.
     * @param string $token
     * @param int    $userid
     * @return bool
     */
    public function validateToken(string $token, int $userid): bool
    {
        $user = new CurrentUser($this->config);
        $user->getUserById($userid);
        $secret = $user->getUserData('secret');

        return $this->twoFactorAuth->verifyCode($secret, $token);
    }

    /**
     * Returns a QR-Code to a given secret for transmitting the secret to the Authentificator-App
     * @param string $secret
     * @return string
     * @throws TwoFactorAuthException
     */
    public function getQrCode(string $secret): string
    {
        return $this->twoFactorAuth->getQRCodeImageAsDataUri($this->config->getTitle(), $secret);
    }
}
