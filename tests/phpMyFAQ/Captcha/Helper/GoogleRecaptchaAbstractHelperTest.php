<?php

/**
 * Test case for GoogleRecaptchaAbstractHelper
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

namespace phpMyFAQ\Tests\Captcha\Helper;

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Captcha\Helper\GoogleRecaptchaAbstractHelper;
use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Configuration;

/**
 * Class GoogleRecaptchaAbstractHelperTest
 */
class GoogleRecaptchaAbstractHelperTest extends TestCase
{
    private Configuration $configuration;
    private GoogleRecaptchaAbstractHelper $helper;
    private CaptchaInterface $captcha;

    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->helper = new GoogleRecaptchaAbstractHelper($this->configuration);
        $this->captcha = $this->createMock(CaptchaInterface::class);
    }

    /**
     * Test constructor initialization
     */
    public function testConstructor(): void
    {
        $helper = new GoogleRecaptchaAbstractHelper($this->configuration);
        $this->assertInstanceOf(GoogleRecaptchaAbstractHelper::class, $helper);
    }

    /**
     * Test renderCaptcha when Google reCAPTCHA is enabled and user is not authenticated
     */
    public function testRenderCaptchaWhenEnabledAndNotAuthenticated(): void
    {
        // Configure captcha to be enabled
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', 'test-site-key-123']
            ]);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'refresh-captcha',
            'Google reCAPTCHA',
            false
        );

        // Assertions for HTML structure
        $this->assertStringContainsString('Google reCAPTCHA', $result);
        $this->assertStringContainsString('class="row mb-2"', $result);
        $this->assertStringContainsString('col-sm-3 col-form-label', $result);
        $this->assertStringContainsString('col-sm-9', $result);

        // Assertions for Google reCAPTCHA specific elements
        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js', $result);
        $this->assertStringContainsString('async defer', $result);
        $this->assertStringContainsString('g-recaptcha', $result);
        $this->assertStringContainsString('data-sitekey="test-site-key-123"', $result);
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
            'Google reCAPTCHA',
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
            'Google reCAPTCHA',
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
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', '']
            ]);

        $result = $this->helper->renderCaptcha($this->captcha);

        $this->assertStringContainsString('g-recaptcha', $result);
        $this->assertStringContainsString('data-sitekey=""', $result);
    }

    /**
     * Test renderCaptcha with special characters in site key
     */
    public function testRenderCaptchaWithSpecialCharactersSiteKey(): void
    {
        $specialSiteKey = 'test-key-with-&-special-<chars>';

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', $specialSiteKey]
            ]);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'test-action',
            'Test Label',
            false
        );

        $this->assertStringContainsString($specialSiteKey, $result);
        $this->assertStringContainsString('data-sitekey="' . $specialSiteKey . '"', $result);
    }

    /**
     * Test renderCaptcha HTML structure and Bootstrap classes
     */
    public function testRenderCaptchaHtmlStructure(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', 'valid-site-key']
            ]);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'test-action',
            'Test Label',
            false
        );

        // Test Bootstrap grid classes
        $this->assertStringContainsString('row mb-2', $result);
        $this->assertStringContainsString('col-sm-3 col-form-label', $result);
        $this->assertStringContainsString('col-sm-9', $result);

        // Test proper div structure
        $this->assertStringContainsString('<div class="row mb-2">', $result);
        $this->assertStringContainsString('</div>', $result);

        // Test script tag attributes
        $this->assertStringContainsString('<script src=', $result);
        $this->assertStringContainsString('async defer', $result);
    }

    /**
     * Test renderCaptcha with long site key
     */
    public function testRenderCaptchaWithLongSiteKey(): void
    {
        $longSiteKey = str_repeat('a', 200); // Very long site key

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', $longSiteKey]
            ]);

        $result = $this->helper->renderCaptcha(
            $this->captcha,
            'test-action',
            'Test Label',
            false
        );

        $this->assertStringContainsString($longSiteKey, $result);
        $this->assertStringContainsString('g-recaptcha', $result);
    }

    /**
     * Test renderCaptcha with multilingual labels
     */
    public function testRenderCaptchaWithMultilingualLabels(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', 'test-key']
            ]);

        $multilingualLabels = [
            'English Label',
            'Deutscher Label',
            'Étiquette française',
            'Etiqueta española',
            '中文标签',
            'Русская метка'
        ];

        foreach ($multilingualLabels as $label) {
            $result = $this->helper->renderCaptcha(
                $this->captcha,
                'test-action',
                $label,
                false
            );

            $this->assertStringContainsString($label, $result);
            $this->assertStringContainsString('col-form-label', $result);
        }
    }

    /**
     * Test renderCaptcha method parameters independence
     */
    public function testRenderCaptchaParametersIndependence(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['spam.enableCaptchaCode', true],
                ['security.googleReCaptchaV2SiteKey', 'test-key']
            ]);

        // Test that action parameter doesn't affect output for Google reCAPTCHA
        $result1 = $this->helper->renderCaptcha($this->captcha, 'action1', 'Label', false);
        $result2 = $this->helper->renderCaptcha($this->captcha, 'action2', 'Label', false);

        // Both should contain the same reCAPTCHA elements regardless of action
        $this->assertStringContainsString('g-recaptcha', $result1);
        $this->assertStringContainsString('g-recaptcha', $result2);
        $this->assertStringContainsString('google.com/recaptcha', $result1);
        $this->assertStringContainsString('google.com/recaptcha', $result2);
    }
}
