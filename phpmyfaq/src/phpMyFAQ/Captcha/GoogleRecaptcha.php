<?php

/**
 * The phpMyFAQ Google ReCAPTCHA v3 class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Captcha;

use phpMyFAQ\Configuration;

class GoogleRecaptcha implements CaptchaInterface
{
    private bool $userIsLoggedIn;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function checkCaptchaCode(string $code): bool
    {
        if ($this->isUserIsLoggedIn()) {
            return true;
        }

        $url = sprintf(
            'https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s',
            $this->configuration->get(item: 'security.googleReCaptchaV2SecretKey'),
            $code,
        );

        $response = false;
        set_error_handler(static fn() => true);
        try {
            $response = file_get_contents($url);
        } finally {
            restore_error_handler();
        }

        if (!is_string($response) || $response === '') {
            return false;
        }

        try {
            $decoded = json_decode($response, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        return is_array($decoded) && ($decoded['success'] ?? false) === true;
    }

    public function isUserIsLoggedIn(): bool
    {
        return $this->userIsLoggedIn;
    }

    public function setUserIsLoggedIn(bool $userIsLoggedIn): GoogleRecaptcha
    {
        $this->userIsLoggedIn = $userIsLoggedIn;
        return $this;
    }
}
