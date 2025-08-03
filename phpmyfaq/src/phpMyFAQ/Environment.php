<?php

/**
 * Environment configuration manager
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
 * @since     2025-08-03
 */

namespace phpMyFAQ;

use Symfony\Component\Dotenv\Dotenv;

class Environment
{
    private static bool $debugMode = false;
    private static int $debugLevel = 0;
    private static bool $debugLogQueries = false;
    private static bool $initialized = false;
    private static string $environment = 'production';

    private static bool $testMode = false;
    private static ?Dotenv $dotenv = null;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        if (!self::$testMode) {
            self::loadEnvironment();
        }

        self::setupDebugMode();
        self::$initialized = true;
    }

    public static function enableTestMode(): void
    {
        self::$testMode = true;
    }

    public static function reset(): void
    {
        self::$debugMode = false;
        self::$debugLevel = 0;
        self::$debugLogQueries = false;
        self::$initialized = false;
        self::$environment = 'production';
        self::$testMode = false;
        self::$dotenv = null;
    }

    private static function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__, 2);
        $envFile = $envPath . '/.env';

        if (file_exists($envFile)) {
            self::$dotenv = new Dotenv();
            self::$dotenv->load($envFile);
        }
    }

    private static function setupDebugMode(): void
    {
        self::$debugMode = filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        self::$debugLevel = (int) ($_ENV['DEBUG_LEVEL'] ?? 0);
        self::$debugLogQueries = filter_var($_ENV['DEBUG_LOG_QUERIES'] ?? false, FILTER_VALIDATE_BOOLEAN);
        self::$environment = $_ENV['APP_ENV'] ?? 'production';

        // Legacy support
        if (!defined('DEBUG')) {
            define('DEBUG', self::$debugMode);
        }

        // Error reporting based on debug mode
        if (self::$debugMode) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
        }
    }

    public static function isDebugMode(): bool
    {
        return self::$debugMode;
    }

    public static function getDebugLevel(): int
    {
        return self::$debugLevel;
    }

    public static function shouldLogQueries(): bool
    {
        return self::$debugLogQueries;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

    public static function getEnvironment(): string
    {
        return self::$environment;
    }

    public static function isProduction(): bool
    {
        return self::getEnvironment() === 'production';
    }

    public static function isDevelopment(): bool
    {
        return self::getEnvironment() === 'development';
    }
}
