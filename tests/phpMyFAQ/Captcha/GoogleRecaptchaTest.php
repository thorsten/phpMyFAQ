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
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Tests\Captcha;

use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Captcha\GoogleRecaptcha;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

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
        $this->googleRecaptcha = new GoogleRecaptcha($this->configuration);
    }

    /**
     * Test constructor initialization
     */
    public function testConstructor(): void
    {
        $googleRecaptcha = new GoogleRecaptcha($this->configuration);
        $this->assertInstanceOf(GoogleRecaptcha::class, $googleRecaptcha);
        $this->assertInstanceOf(CaptchaInterface::class, $googleRecaptcha);
    }

    /**
     * Test isUserIsLoggedIn default state
     */
    public function testIsUserIsLoggedInDefaultState(): void
    {
        // Set initial state first since property is uninitialized by default
        $this->googleRecaptcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    /**
     * Test setUserIsLoggedIn and isUserIsLoggedIn
     */
    public function testSetAndIsUserIsLoggedIn(): void
    {
        // Test setting to true
        $result = $this->googleRecaptcha->setUserIsLoggedIn(true);
        $this->assertInstanceOf(GoogleRecaptcha::class, $result); // Fluent interface
        $this->assertTrue($this->googleRecaptcha->isUserIsLoggedIn());

        // Test setting to false
        $this->googleRecaptcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    /**
     * Test checkCaptchaCode when user is logged in (bypass)
     */
    public function testCheckCaptchaCodeWhenLoggedIn(): void
    {
        $this->googleRecaptcha->setUserIsLoggedIn(true);

        // Should return true without making HTTP request when logged in
        $this->assertTrue($this->googleRecaptcha->checkCaptchaCode('any-code'));
        $this->assertTrue($this->googleRecaptcha->checkCaptchaCode(''));
        $this->assertTrue($this->googleRecaptcha->checkCaptchaCode('invalid-token'));
    }

    /**
     * Test checkCaptchaCode when user is not logged in
     */
    public function testCheckCaptchaCodeWhenNotLoggedIn(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return json_encode(['success' => true]);
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration
            ->expects($this->once())
            ->method('get')
            ->with('security.googleReCaptchaV2SecretKey')
            ->willReturn('test-secret-key');

        self::assertTrue($recaptcha->checkCaptchaCode('valid-token'));
    }

    /**
     * Test fluent interface chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->googleRecaptcha->setUserIsLoggedIn(true)->setUserIsLoggedIn(false);

        $this->assertInstanceOf(GoogleRecaptcha::class, $result);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());
    }

    /**
     * Test authentication state changes
     */
    public function testAuthenticationStateChanges(): void
    {
        // Test multiple state changes
        $this->googleRecaptcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->googleRecaptcha->isUserIsLoggedIn());

        $this->googleRecaptcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->googleRecaptcha->isUserIsLoggedIn());

        $this->googleRecaptcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->googleRecaptcha->isUserIsLoggedIn());
    }

    /**
     * Test configuration integration
     */
    public function testConfigurationIntegration(): void
    {
        $this->configuration
            ->expects($this->once())
            ->method('get')
            ->with('security.googleReCaptchaV2SecretKey')
            ->willReturn('mock-secret-key');

        $this->googleRecaptcha->setUserIsLoggedIn(false);

        set_error_handler(static fn(): bool => true);

        try {
            // We only assert integration with configuration; runtime behavior depends on network availability.
            $result = $this->googleRecaptcha->checkCaptchaCode('test-token');
            $this->assertIsBool($result);
        } catch (\Throwable $e) {
            $this->assertNotEmpty($e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Test checkCaptchaCode with empty response token
     */
    public function testCheckCaptchaCodeWithEmptyToken(): void
    {
        $this->googleRecaptcha->setUserIsLoggedIn(false);

        $this->configuration
            ->expects($this->once())
            ->method('get')
            ->with('security.googleReCaptchaV2SecretKey')
            ->willReturn('test-secret');

        set_error_handler(static fn(): bool => true);
        try {
            $this->assertFalse($this->googleRecaptcha->checkCaptchaCode(''));
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Test object state independence
     */
    public function testObjectStateIndependence(): void
    {
        $recaptcha1 = new GoogleRecaptcha($this->configuration);
        $recaptcha2 = new GoogleRecaptcha($this->configuration);

        $recaptcha1->setUserIsLoggedIn(true);
        $recaptcha2->setUserIsLoggedIn(false);

        // Verify independence
        $this->assertTrue($recaptcha1->isUserIsLoggedIn());
        $this->assertFalse($recaptcha2->isUserIsLoggedIn());
    }

    /**
     * Test authentication bypass security
     */
    public function testAuthenticationBypassSecurity(): void
    {
        // When logged in, should always return true regardless of input
        $this->googleRecaptcha->setUserIsLoggedIn(true);

        $maliciousInputs = [
            '<script>alert("xss")</script>',
            'javascript:alert(1)',
            '../../etc/passwd',
            'SELECT * FROM users',
            str_repeat('A', 10000), // Very long string
        ];

        foreach ($maliciousInputs as $input) {
            $this->assertTrue($this->googleRecaptcha->checkCaptchaCode($input));
        }
    }

    /**
     * Test checkCaptchaCode returns false when API returns non-success JSON.
     */
    public function testCheckCaptchaCodeReturnsFalseForFailedVerification(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return json_encode(['success' => false]);
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertFalse($recaptcha->checkCaptchaCode('invalid-token'));
    }

    /**
     * Test checkCaptchaCode returns true for successful verification.
     */
    public function testCheckCaptchaCodeReturnsTrueForSuccessfulVerification(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return json_encode(['success' => true]);
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertTrue($recaptcha->checkCaptchaCode('valid-token'));
    }

    /**
     * Test checkCaptchaCode returns false for malformed JSON response.
     */
    public function testCheckCaptchaCodeReturnsFalseForMalformedJson(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return 'not valid json {{{';
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertFalse($recaptcha->checkCaptchaCode('some-token'));
    }

    /**
     * Test checkCaptchaCode returns false for empty string response.
     */
    public function testCheckCaptchaCodeReturnsFalseForEmptyStringResponse(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return '';
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertFalse($recaptcha->checkCaptchaCode('some-token'));
    }

    /**
     * Test checkCaptchaCode returns false when fetchUrl returns false.
     */
    public function testCheckCaptchaCodeReturnsFalseWhenFetchFails(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return false;
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertFalse($recaptcha->checkCaptchaCode('some-token'));
    }

    /**
     * Test checkCaptchaCode returns false when JSON has no success field.
     */
    public function testCheckCaptchaCodeReturnsFalseWhenSuccessFieldMissing(): void
    {
        $recaptcha = new class($this->configuration) extends GoogleRecaptcha {
            protected function fetchUrl(string $url): string|false
            {
                return json_encode(['error' => 'something went wrong']);
            }
        };
        $recaptcha->setUserIsLoggedIn(false);

        $this->configuration->method('get')->willReturn('test-secret');

        self::assertFalse($recaptcha->checkCaptchaCode('some-token'));
    }
}
