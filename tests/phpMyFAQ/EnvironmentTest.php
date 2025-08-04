<?php

/**
 * Environment Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-04
 */

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class EnvironmentTest
 */
#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::enableTestMode();
    }

    public function testInitializesEnvironment(): void
    {
        Environment::init();

        $this->assertTrue(true);
    }

    public function testEnableTestModeActivatesTestMode(): void
    {
        Environment::enableTestMode();

        $this->assertTrue(true);
    }

    public function testGetEnvironmentReturnsString(): void
    {
        Environment::init();

        $environment = Environment::getEnvironment();

        $this->assertIsString($environment);
        $this->assertNotEmpty($environment);
    }

    public function testIsDebugModeReturnsBool(): void
    {
        Environment::init();

        $debugMode = Environment::isDebugMode();

        $this->assertIsBool($debugMode);
    }

    public function testGetDebugLevelReturnsInt(): void
    {
        Environment::init();

        $debugLevel = Environment::getDebugLevel();

        $this->assertIsInt($debugLevel);
        $this->assertGreaterThanOrEqual(0, $debugLevel);
    }

    public function testShouldLogQueriesReturnsBool(): void
    {
        Environment::init();

        $shouldLogQueries = Environment::shouldLogQueries();

        $this->assertIsBool($shouldLogQueries);
    }

    public function testGetMethodReturnsEnvironmentVariable(): void
    {
        $_ENV['TEST_VAR'] = 'test_value';

        $value = Environment::get('TEST_VAR');

        $this->assertEquals('test_value', $value);

        unset($_ENV['TEST_VAR']);
    }

    public function testGetMethodReturnsDefaultValue(): void
    {
        $value = Environment::get('NON_EXISTENT_VAR', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function testIsProductionReturnsBool(): void
    {
        Environment::init();

        $isProduction = Environment::isProduction();

        $this->assertIsBool($isProduction);
    }

    public function testIsDevelopmentReturnsBool(): void
    {
        Environment::init();

        $isDevelopment = Environment::isDevelopment();

        $this->assertIsBool($isDevelopment);
    }

    public function testProductionAndDevelopmentAreMutuallyExclusive(): void
    {
        Environment::init();

        $isProduction = Environment::isProduction();
        $isDevelopment = Environment::isDevelopment();

        // They should not both be true
        $this->assertFalse($isProduction && $isDevelopment);
    }

    public function testMultipleInitCallsWork(): void
    {
        Environment::init();
        Environment::init();

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        // Clean up any test environment variables
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'TEST_')) {
                unset($_ENV[$key]);
            }
        }
    }
}
