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
use phpMyFAQ\Template;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use RobThree\Auth\Algorithm;

readonly class TwoFactor
{
    private TwoFactorAuth $twoFactorAuth;

    private EndroidQrCodeProvider $qrCodeProvider;

    /**
     * @throws TwoFactorAuthException
     */
    public function __construct(private Configuration $configuration)
    {
        $this->qrCodeProvider = new EndroidQrCodeProvider();
        $this->twoFactorAuth = new TwoFactorAuth(
            $this->configuration->get('main.metaPublisher'),
            6,
            30,
            Algorithm::Sha1,
            $this->qrCodeProvider
        );
    }

    /**
     * Generates and returns a new secret without saving
     * @throws TwoFactorAuthException
     */
    public function generateSecret(): string
    {
        return $this->twoFactorAuth->createSecret();
    }

    /**
     * Saves a given secret to the current user from the session.
     *
     * @return true
     */
    public function saveSecret(string $secret): bool
    {
        $user = CurrentUser::getFromSession($this->configuration);
        $user->setUserData(['secret' => $secret]);
        return true;
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
     *
     * @throws Exception
*/
    public function validateToken(string $token, int $userid): bool
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userid);

        $secret = $currentUser->getUserData('secret');

        return $this->twoFactorAuth->verifyCode($secret, $token);
    }

    /**
     * Returns a QR-Code to a given secret for transmitting the secret to the Authenticator-App
     *
     * @throws Exception
     */
    public function getQrCode(string $secret): string
    {
        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        $label = $this->configuration->getTitle() . ':' . $currentUser->getUserData('email');
        $qrCodeText = sprintf(
            '%s&image=%sassets/themes/%s/img/logo.png',
            $this->twoFactorAuth->getQrText($label, $secret),
            $this->configuration->getDefaultUrl(),
            Template::getTplSetName()
        );

        return sprintf(
            'data:%s;base64,%s',
            $this->qrCodeProvider->getMimeType(),
            base64_encode($this->qrCodeProvider->getQRCodeImage($qrCodeText, 200))
        );
    }
}
