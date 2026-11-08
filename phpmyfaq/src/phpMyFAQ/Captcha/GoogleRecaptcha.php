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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleRecaptcha implements CaptchaInterface
{
    private const string VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private bool $userIsLoggedIn = false;

    private readonly HttpClientInterface $httpClient;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function checkCaptchaCode(string $code): bool
    {
        if ($this->isUserIsLoggedIn()) {
            return true;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => (string) $this->configuration->get(item: 'security.googleReCaptchaV2SecretKey'),
                    'response' => $code,
                ],
            ]);

            $data = $response->toArray();
        } catch (ExceptionInterface) {
            return false;
        }

        return ($data['success'] ?? false) === true;
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
