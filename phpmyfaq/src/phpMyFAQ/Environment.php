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
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-03
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

class Environment
{
    private static bool $debugMode = false;

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

    private static function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__, levels: 2);
        $envFile = $envPath . '/.env';

        if (file_exists($envFile)) {
            self::$dotenv = new Dotenv();
            self::$dotenv->load($envFile);
        }
    }

    private static function setupDebugMode(): void
    {
        if (self::$testMode) {
            return;
        }

        self::$debugMode = filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        self::$debugLogQueries = filter_var($_ENV['DEBUG_LOG_QUERIES'] ?? false, FILTER_VALIDATE_BOOLEAN);
        self::$environment = $_ENV['APP_ENV'] ?? 'production';

        error_reporting(self::$debugMode ? E_ALL : E_ERROR | E_WARNING | E_PARSE);

        if (self::$debugMode) {
            Debug::enable();
            ErrorHandler::register();
            return;
        }

        $handler = ErrorHandler::register();

        $logger = new Logger(name: 'phpmyfaq');
        $logTarget = $_ENV['ERROR_LOG'] ?? 'php://stderr';
        $logger->pushHandler(new StreamHandler($logTarget, Level::Warning));

        $levelsMap = [
            E_DEPRECATED => null,
            E_USER_DEPRECATED => null,
        ];
        $handler->setDefaultLogger($logger, $levelsMap);

        $handler->screamAt(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        $handler->scopeAt(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    public static function isDebugMode(): bool
    {
        return self::$debugMode;
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
