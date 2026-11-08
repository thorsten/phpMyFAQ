<?php

/**
 * Test case for GoogleRecaptcha
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Tests\Captcha;

use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Captcha\GoogleRecaptcha;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Class GoogleRecaptchaTest
 */
#[AllowMockObjectsWithoutExpectations]
class GoogleRecaptchaTest extends TestCase
{
    private Configuration $configuration;
    private GoogleRecaptcha $googleRecaptcha;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->googleRecaptcha = new GoogleRecaptcha($this->configuration, new MockHttpClient());
    }

    public function testConstructor(): void
    {
        $googleRecaptcha = new GoogleRecaptcha($this->configuration);
        $this->assertInstanceOf(GoogleRecaptcha::class, $googleRecaptcha);
        $this->assertInstanceOf(CaptchaInterface::class, $googleRecaptcha);
    }

    public function testIsUserIsLoggedInDefaultState(): void
    {
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    public function testSetAndIsUserIsLoggedIn(): void
    {
        $result = $this->googleRecaptcha->setUserIsLoggedIn(true);
        $this->assertInstanceOf(GoogleRecaptcha::class, $result);
        $this->assertTrue($this->googleRecaptcha->isUserIsLoggedIn());

        $this->googleRecaptcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    public function testCheckCaptchaCodeWhenLoggedInSkipsHttpRequest(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            self::fail('No HTTP request must be made when the user is logged in.');
        });
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(true);

        $this->assertTrue($googleRecaptcha->checkCaptchaCode('any-code'));
        $this->assertTrue($googleRecaptcha->checkCaptchaCode(''));
        $this->assertTrue($googleRecaptcha->checkCaptchaCode('invalid-token'));
        $this->assertSame(0, $httpClient->getRequestsCount());
    }

    public function testCheckCaptchaCodeSendsSecretAndResponseToGoogle(): void
    {
        $this->configuration->method('get')->with('security.googleReCaptchaV2SecretKey')->willReturn('test-secret');

        $captured = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url, 'body' => $options['body']];
            return new MockResponse(json_encode(['success' => true]), ['response_headers' => ['content-type' => 'application/json']]);
        });
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(false);

        $this->assertTrue($googleRecaptcha->checkCaptchaCode('user-token'));
        $this->assertSame('POST', $captured['method']);
        $this->assertSame('https://www.google.com/recaptcha/api/siteverify', $captured['url']);
        $this->assertSame('secret=test-secret&response=user-token', $captured['body']);
    }

    public function testCheckCaptchaCodeReturnsFalseWhenGoogleRejects(): void
    {
        $this->configuration->method('get')->willReturn('test-secret');
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['success' => false, 'error-codes' => ['invalid-input-response']]),
            ['response_headers' => ['content-type' => 'application/json']],
        ));
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(false);

        $this->assertFalse($googleRecaptcha->checkCaptchaCode('bad-token'));
    }

    public function testCheckCaptchaCodeReturnsFalseOnHttpError(): void
    {
        $this->configuration->method('get')->willReturn('test-secret');
        $httpClient = new MockHttpClient(new MockResponse('Internal Server Error', ['http_code' => 500]));
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(false);

        $this->assertFalse($googleRecaptcha->checkCaptchaCode('token'));
    }

    public function testCheckCaptchaCodeReturnsFalseOnInvalidJson(): void
    {
        $this->configuration->method('get')->willReturn('test-secret');
        $httpClient = new MockHttpClient(new MockResponse('not json', ['response_headers' => ['content-type' => 'application/json']]));
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(false);

        $this->assertFalse($googleRecaptcha->checkCaptchaCode('token'));
    }

    public function testCheckCaptchaCodeReturnsFalseWhenSuccessKeyMissing(): void
    {
        $this->configuration->method('get')->willReturn('test-secret');
        $httpClient = new MockHttpClient(new MockResponse(
            json_encode(['challenge_ts' => '2026-01-01T00:00:00Z']),
            ['response_headers' => ['content-type' => 'application/json']],
        ));
        $googleRecaptcha = new GoogleRecaptcha($this->configuration, $httpClient);
        $googleRecaptcha->setUserIsLoggedIn(false);

        $this->assertFalse($googleRecaptcha->checkCaptchaCode('token'));
    }

    public function testFluentInterface(): void
    {
        $result = $this->googleRecaptcha->setUserIsLoggedIn(true)->setUserIsLoggedIn(false);

        $this->assertInstanceOf(GoogleRecaptcha::class, $result);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    public function testObjectStateIndependence(): void
    {
        $recaptcha1 = new GoogleRecaptcha($this->configuration, new MockHttpClient());
        $recaptcha2 = new GoogleRecaptcha($this->configuration, new MockHttpClient());

        $recaptcha1->setUserIsLoggedIn(true);
        $recaptcha2->setUserIsLoggedIn(false);

        $this->assertTrue($recaptcha1->isUserIsLoggedIn());
        $this->assertFalse($recaptcha2->isUserIsLoggedIn());
    }

    public function testAuthenticationBypassSecurity(): void
    {
        $this->googleRecaptcha->setUserIsLoggedIn(true);

        $maliciousInputs = [
            '<script>alert("xss")</script>',
            'javascript:alert(1)',
            '../../etc/passwd',
            'SELECT * FROM users',
            str_repeat('A', 10000),
        ];

        foreach ($maliciousInputs as $input) {
            $this->assertTrue($this->googleRecaptcha->checkCaptchaCode($input));
        }
    }
}
