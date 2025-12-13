<?php

/**
 * Test case for BuiltinCaptchaAbstractHelper
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

namespace phpMyFAQ\Captcha\Helper;

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class BuiltinCaptchaAbstractHelperTest
 */
#[AllowMockObjectsWithoutExpectations]
class BuiltinCaptchaAbstractHelperTest extends TestCase
{
    private Configuration $configuration;
    private BuiltinCaptchaAbstractHelper $helper;
    private BuiltinCaptcha $captcha;

    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->helper = new BuiltinCaptchaAbstractHelper($this->configuration);
        $this->captcha = $this->createStub(BuiltinCaptcha::class);
    }

    /**
     * Test constructor initialization
     */
    public function testConstructor(): void
    {
        $helper = new BuiltinCaptchaAbstractHelper($this->configuration);
        $this->assertInstanceOf(BuiltinCaptchaAbstractHelper::class, $helper);
    }

    /**
     * Test renderCaptcha when captcha is enabled and user is not authenticated
     */
    public function testRenderCaptchaWhenEnabledAndNotAuthenticated(): void
    {
        // Configure captcha to be enabled
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(true);

        // Mock captcha behavior
        $this->captcha->captchaLength = 5;
        $this->captcha
            ->method('renderCaptchaImage')
            ->willReturn('<img src="captcha.png" alt="Captcha">');

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'refresh-captcha',
            'Captcha Label',
            false
        );

        // Assertions
        $this->assertStringContainsString('Captcha Label', $result);
        $this->assertStringContainsString('name="captcha"', $result);
        $this->assertStringContainsString('id="captcha"', $result);
        $this->assertStringContainsString('size="5"', $result);
        $this->assertStringContainsString('data-action="refresh-captcha"', $result);
        $this->assertStringContainsString('<img src="captcha.png" alt="Captcha">', $result);
        $this->assertStringContainsString('class="row g-4"', $result);
        $this->assertStringContainsString('required', $result);
        $this->assertStringContainsString('autocomplete="off"', $result);
        $this->assertStringContainsString('bi bi-arrow-repeat', $result);
    }

    /**
     * Test renderCaptcha when captcha is disabled
     */
    public function testRenderCaptchaWhenDisabled(): void
    {
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(false);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'refresh-captcha',
            'Captcha Label',
            false
        );

        $this->assertEmpty($result);
    }

    /**
     * Test renderCaptcha when user is authenticated
     */
    public function testRenderCaptchaWhenAuthenticated(): void
    {
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(true);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'refresh-captcha',
            'Captcha Label',
            true // User is authenticated
        );

        $this->assertEmpty($result);
    }

    /**
     * Test renderCaptcha with empty parameters
     */
    public function testRenderCaptchaWithEmptyParameters(): void
    {
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(true);

        $this->captcha->captchaLength = 6;
        $this->captcha
            ->method('renderCaptchaImage')
            ->willReturn('');

        $result = $this->helper->renderCaptcha($this->captcha);

        $this->assertStringContainsString('name="captcha"', $result);
        $this->assertStringContainsString('size="6"', $result);
        $this->assertStringContainsString('data-action=""', $result);
    }

    /**
     * Test renderCaptcha HTML structure and Bootstrap classes
     */
    public function testRenderCaptchaHtmlStructure(): void
    {
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(true);

        $this->captcha->captchaLength = 4;
        $this->captcha
            ->method('renderCaptchaImage')
            ->willReturn('<img src="test.png">');

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'test-action',
            'Test Label',
            false
        );

        // Test Bootstrap grid classes
        $this->assertStringContainsString('col-md-3 col-sm-12 col-form-label', $result);
        $this->assertStringContainsString('col-md-4 col-sm-6 col-7', $result);
        $this->assertStringContainsString('col-md-5 col-sm-6 col-5', $result);

        // Test form classes
        $this->assertStringContainsString('form-control-static', $result);
        $this->assertStringContainsString('form-control', $result);
        $this->assertStringContainsString('input-group', $result);
        $this->assertStringContainsString('input-group-btn', $result);
        $this->assertStringContainsString('btn btn-primary', $result);

        // Test button structure
        $this->assertStringContainsString('id="captcha-button"', $result);
        $this->assertStringContainsString('type="button"', $result);
    }

    /**
     * Test renderCaptcha with special characters in parameters
     */
    public function testRenderCaptchaWithSpecialCharacters(): void
    {
        $this->configuration
            ->method('get')
            ->with('spam.enableCaptchaCode')
            ->willReturn(true);

        $this->captcha->captchaLength = 5;
        $this->captcha
            ->method('renderCaptchaImage')
            ->willReturn('<img src="test.png">');

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'test-action&special=true',
            'Label with <script>alert("test")</script>',
            false
        );

        $this->assertStringContainsString('data-action="test-action&special=true"', $result);
        $this->assertStringContainsString('Label with <script>alert("test")</script>', $result);
    }
}
