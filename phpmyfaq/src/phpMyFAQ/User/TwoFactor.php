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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

class TwoFactor
{
    private TwoFactorAuth $twoFactorAuth;

    private EndroidQrCodeProvider $endroidQrCodeProvider;

    /**
     * Number of adjacent time slices accepted besides the current one.
     *
     * RobThree defaults to 1, which keeps a code usable for roughly 90 seconds and
     * leaves a captured code replayable for that whole span. Accepting only the
     * current slice shortens that to at most 30 seconds. The trade-off is that
     * authenticator apps whose clock has drifted by more than one period are no
     * longer tolerated.
     */
    private const int VERIFY_DISCREPANCY = 0;

    /**
     * Length of a TOTP time slice in seconds.
     */
    private const int PERIOD = 30;

    /**
     * @throws TwoFactorAuthException
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
    ) {
        $this->endroidQrCodeProvider = new EndroidQrCodeProvider();
        $this->twoFactorAuth = new TwoFactorAuth(
            $this->endroidQrCodeProvider,
            (string) $this->configuration->get(item: 'main.titleFAQ'),
            6,
            self::PERIOD,
            Algorithm::Sha1,
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
     */
    public function saveSecret(#[\SensitiveParameter] string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        return $this->currentUser->setUserData(['secret' => $secret]);
    }

    /**
     * Returns the secret of the current user
     */
    public function getSecret(CurrentUser $currentUser): ?string
    {
        $secret = $currentUser->getUserData('secret');

        return is_string($secret) ? $secret : null;
    }

    /**
     * Validates a given token. Returns true if the token is correct.
     */
    public function validateToken(#[\SensitiveParameter] string $token, int $userId): bool
    {
        if (strlen($token) !== 6 || $userId <= 0) {
            return false;
        }

        $this->currentUser->getUserById($userId);

        $secret = $this->currentUser->getUserData('secret');
        if (!is_string($secret) || $secret === '') {
            return false;
        }

        if (!$this->twoFactorAuth->verifyCode($secret, $token, self::VERIFY_DISCREPANCY)) {
            return false;
        }

        // A code is valid for a whole time slice, so a captured code could be replayed
        // until the slice ends. Every slice therefore authenticates at most once.
        $slice = intdiv(time(), self::PERIOD);
        if ($slice <= $this->lastAcceptedSlice($userId)) {
            return false;
        }

        return $this->rememberAcceptedSlice($userId, $slice);
    }

    /**
     * Returns the last time slice a code was accepted for, or 0 if none was recorded.
     */
    private function lastAcceptedSlice(int $userId): int
    {
        $db = $this->configuration->getDb();
        $result = $db->query(sprintf(
            'SELECT twofactor_last_slice FROM %sfaquserdata WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        ));

        if (!$result || $db->numRows($result) !== 1) {
            return 0;
        }

        $row = $db->fetchArray($result);

        return is_array($row) ? (int) ($row['twofactor_last_slice'] ?? 0) : 0;
    }

    private function rememberAcceptedSlice(int $userId, int $slice): bool
    {
        return (bool) $this->configuration
            ->getDb()
            ->query(sprintf(
                'UPDATE %sfaquserdata SET twofactor_last_slice = %d WHERE user_id = %d',
                Database::getTablePrefix(),
                $slice,
                $userId,
            ));
    }

    /**
     * Returns a QR-Code to a given secret for transmitting the secret to the Authenticator-App
     */
    public function getQrCode(#[\SensitiveParameter] string $secret): string
    {
        $label = $this->configuration->getTitle() . ':' . (string) $this->currentUser->getUserData('email');
        $qrCodeText = sprintf(
            '%s&image=%sassets/templates/images/logo.png',
            $this->twoFactorAuth->getQrText($label, $secret),
            $this->configuration->getDefaultUrl(),
        );

        return sprintf(
            'data:%s;base64,%s',
            $this->endroidQrCodeProvider->getMimeType(),
            base64_encode($this->endroidQrCodeProvider->getQRCodeImage($qrCodeText, 200)),
        );
    }
}
