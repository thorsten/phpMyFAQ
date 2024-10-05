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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use RobThree\Auth\Algorithm;

class TwoFactor
{
    private TwoFactorAuth $twoFactorAuth;

    private EndroidQrCodeProvider $qrCodeProvider;

    /**
     * @throws TwoFactorAuthException
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser
    ) {
        $this->qrCodeProvider = new EndroidQrCodeProvider();
        $this->twoFactorAuth = new TwoFactorAuth(
            $this->qrCodeProvider,
            $this->configuration->get('main.metaPublisher'),
            6,
            30,
            Algorithm::Sha1
        );
    }

    /**
     * Generates and returns a new secret without saving
     */
    public function generateSecret(): string
    {
        return $this->twoFactorAuth->createSecret();
    }

    /**
     * Saves a given secret to the current user from the session.
     *
     * @param string $secret
     * @return bool
     */
    public function saveSecret(string $secret): bool
    {
        if (strlen($secret) === 0) {
            return false;
        }

        return $this->currentUser->setUserData(['secret' => $secret]);
    }

    /**
     * Returns the secret of the current user
     */
    public function getSecret(CurrentUser $currentUser): string|null
    {
        return $currentUser->getUserData('secret');
    }

    /**
     * Validates a given token. Returns true if the token is correct.
     */
    public function validateToken(string $token, int $userId): bool
    {
        if (strlen($token) !== 6) {
            return false;
        }

        $this->currentUser->getUserById($userId);

        return $this->twoFactorAuth->verifyCode($this->currentUser->getUserData('secret'), $token);
    }

    /**
     * Returns a QR-Code to a given secret for transmitting the secret to the Authenticator-App
     */
    public function getQrCode(string $secret): string
    {
        $label = $this->configuration->getTitle() . ':' . $this->currentUser->getUserData('email');
        $qrCodeText = sprintf(
            '%s&image=%sassets/templates/images/logo.png',
            $this->twoFactorAuth->getQrText($label, $secret),
            $this->configuration->getDefaultUrl()
        );

        return sprintf(
            'data:%s;base64,%s',
            $this->qrCodeProvider->getMimeType(),
            base64_encode($this->qrCodeProvider->getQRCodeImage($qrCodeText, 200))
        );
    }
}
