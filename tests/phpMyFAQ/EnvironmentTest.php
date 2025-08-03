<?php

namespace phpMyFAQ;

use phpMyFAQ\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv = $_ENV;
        $_ENV = [];

        // Reset Environment state before each test
        Environment::reset();
        Environment::enableTestMode();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        Environment::reset();
        parent::tearDown();
    }

    public function testInitializationWithDefaults(): void
    {
        Environment::init();
        $this->assertFalse(Environment::isDebugMode());
        $this->assertEquals(0, Environment::getDebugLevel());
        $this->assertFalse(Environment::shouldLogQueries());
        $this->assertEquals('production', Environment::getEnvironment());
    }

    public function testInitializationWithEnvironmentVariables(): void
    {
        $_ENV['DEBUG'] = 'true';
        $_ENV['DEBUG_LEVEL'] = '2';
        $_ENV['DEBUG_LOG_QUERIES'] = 'true';
        $_ENV['APP_ENV'] = 'development';
        Environment::init();
        $this->assertTrue(Environment::isDebugMode());
        $this->assertEquals(2, Environment::getDebugLevel());
        $this->assertTrue(Environment::shouldLogQueries());
        $this->assertEquals('development', Environment::getEnvironment());
    }

    public function testDebugModeBooleanTrue(): void
    {
        $_ENV['DEBUG'] = 'true';
        Environment::init();
        $this->assertTrue(Environment::isDebugMode());
    }

    public function testDebugModeBooleanFalse(): void
    {
        $_ENV['DEBUG'] = 'false';
        Environment::init();
        $this->assertFalse(Environment::isDebugMode());
    }

    public function testDebugModeInvalid(): void
    {
        $_ENV['DEBUG'] = 'invalid';
        Environment::init();
        $this->assertFalse(Environment::isDebugMode());
    }

    public function testDebugLevel0(): void
    {
        $_ENV['DEBUG_LEVEL'] = '0';
        Environment::init();
        $this->assertEquals(0, Environment::getDebugLevel());
    }

    public function testDebugLevel1(): void
    {
        $_ENV['DEBUG_LEVEL'] = '1';
        Environment::init();
        $this->assertEquals(1, Environment::getDebugLevel());
    }

    public function testDebugLevel2(): void
    {
        $_ENV['DEBUG_LEVEL'] = '2';
        Environment::init();
        $this->assertEquals(2, Environment::getDebugLevel());
    }

    public function testDebugLevel3(): void
    {
        $_ENV['DEBUG_LEVEL'] = '3';
        Environment::init();
        $this->assertEquals(3, Environment::getDebugLevel());
    }

    public function testDebugLevelInvalid(): void
    {
        $_ENV['DEBUG_LEVEL'] = 'invalid';
        Environment::init();
        $this->assertEquals(0, Environment::getDebugLevel());
    }

    public function testDebugLogQueriesTrue(): void
    {
        $_ENV['DEBUG_LOG_QUERIES'] = 'true';
        Environment::init();
        $this->assertTrue(Environment::shouldLogQueries());
    }

    public function testDebugLogQueriesFalse(): void
    {
        $_ENV['DEBUG_LOG_QUERIES'] = 'false';
        Environment::init();
        $this->assertFalse(Environment::shouldLogQueries());
    }

    public function testDebugLogQueries0(): void
    {
        $_ENV['DEBUG_LOG_QUERIES'] = '0';
        Environment::init();
        $this->assertFalse(Environment::shouldLogQueries());
    }

    public function testEnvironmentProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';
        Environment::init();
        $this->assertTrue(Environment::isProduction());
        $this->assertFalse(Environment::isDevelopment());
        $this->assertEquals('production', Environment::getEnvironment());
    }

    public function testEnvironmentDevelopment(): void
    {
        $_ENV['APP_ENV'] = 'development';
        Environment::init();
        $this->assertFalse(Environment::isProduction());
        $this->assertTrue(Environment::isDevelopment());
        $this->assertEquals('development', Environment::getEnvironment());
    }

    public function testEnvironmentTesting(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        Environment::init();
        $this->assertFalse(Environment::isProduction());
        $this->assertFalse(Environment::isDevelopment());
        $this->assertEquals('testing', Environment::getEnvironment());
    }

    public function testEnvironmentDefault(): void
    {
        Environment::init();
        $this->assertTrue(Environment::isProduction());
        $this->assertFalse(Environment::isDevelopment());
        $this->assertEquals('production', Environment::getEnvironment());
    }

    public function testLegacyDebugConstant(): void
    {
        $_ENV['DEBUG'] = 'true';
        Environment::init();
        $this->assertTrue(defined('DEBUG'));
    }

    public function testGetEnvironmentDefault(): void
    {
        Environment::init();
        $this->assertEquals('production', Environment::getEnvironment());
    }

    public function testMultipleInitCallsDoNotOverride(): void
    {
        $_ENV['DEBUG'] = 'true';
        $_ENV['DEBUG_LEVEL'] = '3';
        Environment::init();
        $this->assertTrue(Environment::isDebugMode());
        $this->assertEquals(3, Environment::getDebugLevel());

        // Reset for second test
        Environment::reset();
        Environment::enableTestMode();
        $_ENV['DEBUG'] = 'false';
        $_ENV['DEBUG_LEVEL'] = '0';
        Environment::init();

        // Should use new values since we reset
        $this->assertFalse(Environment::isDebugMode());
        $this->assertEquals(0, Environment::getDebugLevel());
    }
}
