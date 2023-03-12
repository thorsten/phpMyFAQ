<?php

/**
 * Class for two-factor-authentification.
 *
 * This class handles all operations around creating, saving and getting the secret
 * for a CurrentUser for two-factor-authentification. It also validates given tokens in
 * comparison to a given secret and returns a qr-code for transmitting a secret to
 * the authenticator-app.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

namespace phpMyFAQ;

use phpMyFAQ\Configuration;
use RobThree\Auth\TwoFactorAuth;
use phpMyFAQ\User\CurrentUser;

class Twofactor
{
    private $config = null;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

  // Generates and returns a new secret without saving
    public function generateSecret(): string
    {
        $tfa = new TwoFactorAuth();
        $secret = $tfa->createSecret();
        return $secret;
    }

  // Saves a given secret to the current user from the session
    public function saveSecret($secret)
    {
        $user = CurrentUser::getFromSession($this->config);
        $user->setUserData(['secret' => $secret]);
        return true;
    }

  // Returns the secret of the current user
    public function getSecret($user)
    {
        return $user->getUserData('secret');
    }

  // Validates a given token. Returns true if the token is correct.
    public function validateToken($token, $userid)
    {
        $user = new CurrentUser($this->config);
        $user->getUserById($userid);
        $secret = $user->getUserData('secret');
        $tfa = new TwoFactorAuth();
        $result = $tfa->verifyCode($secret, $token);
        if ($result == true) {
            return true;
        } else {
            return false;
        }
    }

  // Returns a QR-Code to a given secret for transmitting the secret to the Authentificator-App
    public function getQrCode($secret): string
    {
        $tfa = new TwoFactorAuth();
        $result = $tfa->getQRCodeImageAsDataUri("phpMyFAQ", $secret);
        return $result;
    }
}
