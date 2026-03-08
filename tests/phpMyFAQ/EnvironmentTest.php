<?php

/**
 * Environment Test.
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
 * @since     2025-08-04
 */

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
        $this->resetEnvironmentState();
        Environment::enableTestMode();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        $this->resetEnvironmentState();
    }

    /**
     * Runs a callable that registers error/exception handlers, then restores the original handlers.
     */
    private function withHandlerCleanup(callable $fn): void
    {
        // Capture current handler count by setting temporary handlers
        $origError = set_error_handler(static fn () => false);
        restore_error_handler();
        $origException = set_exception_handler(null);
        restore_exception_handler();

        $fn();

        // Remove handlers added by setupDebugMode by restoring until we get back to the original
        // Pop error handlers until we get back to original
        while (true) {
            $current = set_error_handler(static fn () => false);
            restore_error_handler();
            if ($current === $origError) {
                break;
            }
            restore_error_handler();
        }

        // Pop exception handlers until we get back to original
        while (true) {
            $current = set_exception_handler(null);
            restore_exception_handler();
            if ($current === $origException) {
                break;
            }
            restore_exception_handler();
        }
    }

    private function resetEnvironmentState(): void
    {
        $reflection = new ReflectionClass(Environment::class);

        $props = [
            'debugMode' => false,
            'debugLogQueries' => false,
            'initialized' => false,
            'environment' => 'production',
            'testMode' => false,
            'dotenv' => null,
        ];

        foreach ($props as $name => $default) {
            $prop = $reflection->getProperty($name);
            $prop->setValue(null, $default);
        }
    }

    public function testInitSetsInitializedFlag(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $reflection = new ReflectionClass(Environment::class);
        $initialized = $reflection->getProperty('initialized');

        $this->assertTrue($initialized->getValue());
    }

    public function testInitSkipsWhenAlreadyInitialized(): void
    {
        Environment::enableTestMode();
        Environment::init();

        // Set a custom environment value after init
        $reflection = new ReflectionClass(Environment::class);
        $envProp = $reflection->getProperty('environment');
        $envProp->setValue(null, 'custom');

        // Second init should not overwrite
        Environment::init();

        $this->assertSame('custom', Environment::getEnvironment());
    }

    public function testInitInTestModeSkipsLoadEnvironment(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $reflection = new ReflectionClass(Environment::class);
        $dotenv = $reflection->getProperty('dotenv');

        $this->assertNull($dotenv->getValue());
    }

    public function testEnableTestModeSetsFlag(): void
    {
        Environment::enableTestMode();

        $reflection = new ReflectionClass(Environment::class);
        $testMode = $reflection->getProperty('testMode');

        $this->assertTrue($testMode->getValue());
    }

    public function testIsDebugModeReturnsFalseByDefault(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertFalse(Environment::isDebugMode());
    }

    public function testIsDebugModeReturnsTrueWhenSet(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $debugProp = $reflection->getProperty('debugMode');
        $debugProp->setValue(null, true);

        $this->assertTrue(Environment::isDebugMode());
    }

    public function testShouldLogQueriesReturnsFalseByDefault(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertFalse(Environment::shouldLogQueries());
    }

    public function testShouldLogQueriesReturnsTrueWhenSet(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $prop = $reflection->getProperty('debugLogQueries');
        $prop->setValue(null, true);

        $this->assertTrue(Environment::shouldLogQueries());
    }

    public function testGetReturnsEnvironmentVariable(): void
    {
        $_ENV['TEST_PMF_VAR'] = 'hello';

        $this->assertSame('hello', Environment::get('TEST_PMF_VAR'));
    }

    public function testGetReturnsDefaultWhenVariableNotSet(): void
    {
        $this->assertSame('fallback', Environment::get('NON_EXISTENT_KEY_12345', 'fallback'));
    }

    public function testGetReturnsNullWhenVariableNotSetAndNoDefault(): void
    {
        $this->assertNull(Environment::get('NON_EXISTENT_KEY_12345'));
    }

    public function testGetReturnsIntegerDefault(): void
    {
        $this->assertSame(42, Environment::get('NON_EXISTENT_KEY_12345', 42));
    }

    public function testGetReturnsBooleanDefault(): void
    {
        $this->assertFalse(Environment::get('NON_EXISTENT_KEY_12345', false));
    }

    public function testGetEnvironmentReturnsProductionByDefault(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertSame('production', Environment::getEnvironment());
    }

    public function testGetEnvironmentReturnsCustomValue(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $prop = $reflection->getProperty('environment');
        $prop->setValue(null, 'staging');

        $this->assertSame('staging', Environment::getEnvironment());
    }

    public function testIsProductionReturnsTrueByDefault(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertTrue(Environment::isProduction());
    }

    public function testIsProductionReturnsFalseWhenDevelopment(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $prop = $reflection->getProperty('environment');
        $prop->setValue(null, 'development');

        $this->assertFalse(Environment::isProduction());
    }

    public function testIsDevelopmentReturnsFalseByDefault(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertFalse(Environment::isDevelopment());
    }

    public function testIsDevelopmentReturnsTrueWhenSet(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $prop = $reflection->getProperty('environment');
        $prop->setValue(null, 'development');

        $this->assertTrue(Environment::isDevelopment());
    }

    public function testProductionAndDevelopmentAreMutuallyExclusive(): void
    {
        Environment::enableTestMode();
        Environment::init();

        $this->assertFalse(Environment::isProduction() && Environment::isDevelopment());
    }

    public function testIsNotProductionAndNotDevelopmentForCustomEnvironment(): void
    {
        $reflection = new ReflectionClass(Environment::class);
        $prop = $reflection->getProperty('environment');
        $prop->setValue(null, 'staging');

        $this->assertFalse(Environment::isProduction());
        $this->assertFalse(Environment::isDevelopment());
    }

    public function testSetupDebugModeSkipsInTestMode(): void
    {
        $_ENV['DEBUG'] = 'true';
        $_ENV['APP_ENV'] = 'development';

        Environment::enableTestMode();
        Environment::init();

        // In test mode, setupDebugMode returns early, so debug stays false
        $this->assertFalse(Environment::isDebugMode());
        $this->assertSame('production', Environment::getEnvironment());

        unset($_ENV['DEBUG'], $_ENV['APP_ENV']);
    }

    public function testSetupDebugModeReadsEnvVariablesWhenNotTestMode(): void
    {
        $_ENV['DEBUG'] = 'true';
        $_ENV['DEBUG_LOG_QUERIES'] = 'true';
        $_ENV['APP_ENV'] = 'development';

        $this->withHandlerCleanup(function (): void {
            $reflection = new ReflectionClass(Environment::class);
            $testModeProp = $reflection->getProperty('testMode');
            $testModeProp->setValue(null, false);

            $method = $reflection->getMethod('setupDebugMode');
            $method->invoke(null);

            $this->assertTrue(Environment::isDebugMode());
            $this->assertTrue(Environment::shouldLogQueries());
            $this->assertSame('development', Environment::getEnvironment());
        });

        unset($_ENV['DEBUG'], $_ENV['DEBUG_LOG_QUERIES'], $_ENV['APP_ENV']);
    }

    public function testSetupDebugModeWithDebugDisabled(): void
    {
        $_ENV['DEBUG'] = 'false';
        $_ENV['DEBUG_LOG_QUERIES'] = 'false';
        $_ENV['APP_ENV'] = 'production';
        $_ENV['ERROR_LOG'] = 'php://stderr';

        $this->withHandlerCleanup(function (): void {
            $reflection = new ReflectionClass(Environment::class);
            $testModeProp = $reflection->getProperty('testMode');
            $testModeProp->setValue(null, false);

            $method = $reflection->getMethod('setupDebugMode');
            $method->invoke(null);

            $this->assertFalse(Environment::isDebugMode());
            $this->assertFalse(Environment::shouldLogQueries());
            $this->assertSame('production', Environment::getEnvironment());
        });

        unset($_ENV['DEBUG'], $_ENV['DEBUG_LOG_QUERIES'], $_ENV['APP_ENV'], $_ENV['ERROR_LOG']);
    }

    public function testSetupDebugModeWithoutEnvVariables(): void
    {
        unset($_ENV['DEBUG'], $_ENV['DEBUG_LOG_QUERIES'], $_ENV['APP_ENV']);

        $this->withHandlerCleanup(function (): void {
            $reflection = new ReflectionClass(Environment::class);
            $testModeProp = $reflection->getProperty('testMode');
            $testModeProp->setValue(null, false);

            $method = $reflection->getMethod('setupDebugMode');
            $method->invoke(null);

            $this->assertFalse(Environment::isDebugMode());
            $this->assertFalse(Environment::shouldLogQueries());
            $this->assertSame('production', Environment::getEnvironment());
        });
    }

    public function testSetupDebugModeWithCustomErrorLog(): void
    {
        $_ENV['DEBUG'] = 'false';
        $_ENV['ERROR_LOG'] = '/tmp/phpmyfaq-test.log';

        $this->withHandlerCleanup(function (): void {
            $reflection = new ReflectionClass(Environment::class);
            $testModeProp = $reflection->getProperty('testMode');
            $testModeProp->setValue(null, false);

            $method = $reflection->getMethod('setupDebugMode');
            $method->invoke(null);

            $this->assertFalse(Environment::isDebugMode());
        });

        unset($_ENV['DEBUG'], $_ENV['ERROR_LOG']);
    }

    public function testInitWithoutTestModeCallsLoadEnvironment(): void
    {
        $_ENV['DEBUG'] = 'false';
        $_ENV['ERROR_LOG'] = 'php://stderr';

        $this->withHandlerCleanup(function (): void {
            $reflection = new ReflectionClass(Environment::class);
            $testModeProp = $reflection->getProperty('testMode');
            $testModeProp->setValue(null, false);

            Environment::init();

            $initialized = $reflection->getProperty('initialized');
            $this->assertTrue($initialized->getValue());
        });

        unset($_ENV['DEBUG'], $_ENV['ERROR_LOG']);
    }

    public function testGetOverridesExistingEnvVariable(): void
    {
        $_ENV['TEST_PMF_OVERRIDE'] = 'original';

        $this->assertSame('original', Environment::get('TEST_PMF_OVERRIDE'));

        $_ENV['TEST_PMF_OVERRIDE'] = 'updated';

        $this->assertSame('updated', Environment::get('TEST_PMF_OVERRIDE'));
    }

    public function testGetWithEmptyStringVariable(): void
    {
        $_ENV['TEST_PMF_EMPTY'] = '';

        $this->assertSame('', Environment::get('TEST_PMF_EMPTY'));
        $this->assertSame('', Environment::get('TEST_PMF_EMPTY', 'default'));
    }
}
