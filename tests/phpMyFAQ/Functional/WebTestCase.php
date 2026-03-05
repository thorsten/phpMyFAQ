<?php

/**
 * Base class for phpMyFAQ functional/integration tests
 *
 * Provides a Client that sends requests through the Kernel's HttpKernel,
 * enabling full-stack testing without a web server.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Functional;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class WebTestCase extends TestCase
{
    protected static ?Kernel $kernel = null;

    protected static ?HttpKernelBrowser $client = null;

    private static ?Sqlite3 $dbHandle = null;

    private static ?string $databasePath = null;

    private static ?Configuration $previousConfiguration = null;

    private static ?DatabaseDriver $previousDatabaseDriver = null;

    private static ?string $previousDatabaseType = null;

    private static ?string $previousTablePrefix = null;

    private static bool $hasBackedUpGlobalState = false;

    protected static function createClient(string $routingContext = 'public'): HttpKernelBrowser
    {
        self::backupGlobalState();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-functional-webtest-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        self::$databasePath = $databasePath;

        self::$dbHandle = new Sqlite3();
        self::$dbHandle->connect($databasePath, '', '');
        self::resetConfigurationSingleton();
        new Configuration(self::$dbHandle);
        self::initializeDatabaseStatics(self::$dbHandle);

        static::$kernel = new PhpMyFaqTestKernel($routingContext);
        static::$kernel->boot();
        static::$client = new HttpKernelBrowser(static::$kernel);

        return static::$client;
    }

    protected static function assertResponseStatusCodeSame(int $expectedStatusCode, ?object $response = null): void
    {
        $response = self::resolveResponse($response);
        static::assertSame($expectedStatusCode, $response->getStatusCode());
    }

    protected static function assertResponseIsSuccessful(?object $response = null): void
    {
        $response = self::resolveResponse($response);
        static::assertTrue($response->isSuccessful(), sprintf(
            'Expected successful response, got %d',
            $response->getStatusCode(),
        ));
    }

    protected static function assertResponseHeaderSame(
        string $headerName,
        string $expectedValue,
        ?object $response = null,
    ): void {
        $response = self::resolveResponse($response);
        static::assertSame($expectedValue, $response->headers->get($headerName));
    }

    private static function resolveResponse(?object $response = null): Response
    {
        $response ??= static::$client?->getResponse();
        static::assertInstanceOf(Response::class, $response, 'No Symfony response available. Did you make a request?');

        return $response;
    }

    protected function tearDown(): void
    {
        static::$kernel = null;
        static::$client = null;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbHandle instanceof Sqlite3) {
            self::$dbHandle->close();
        }

        if (self::$databasePath !== null) {
            @unlink(self::$databasePath);
        }

        self::$dbHandle = null;
        self::$databasePath = null;
        static::$kernel = null;
        static::$client = null;
        self::restoreGlobalState();
    }

    private static function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);

        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');

        Database::setTablePrefix('');
    }

    private static function resetConfigurationSingleton(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, null);
    }

    private static function backupGlobalState(): void
    {
        if (self::$hasBackedUpGlobalState) {
            return;
        }

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        self::$previousConfiguration = $configurationProperty->getValue();

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        self::$previousDatabaseDriver = $databaseDriverProperty->getValue();

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        self::$previousDatabaseType = $dbTypeProperty->isInitialized() ? $dbTypeProperty->getValue() : null;

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        self::$previousTablePrefix = $tablePrefixProperty->getValue();

        self::$hasBackedUpGlobalState = true;
    }

    private static function restoreGlobalState(): void
    {
        if (!self::$hasBackedUpGlobalState) {
            return;
        }

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, self::$previousConfiguration);

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, self::$previousDatabaseDriver);

        if (self::$previousDatabaseType !== null) {
            $dbTypeProperty = $databaseReflection->getProperty('dbType');
            $dbTypeProperty->setValue(null, self::$previousDatabaseType);
        }

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        $tablePrefixProperty->setValue(null, self::$previousTablePrefix);

        self::$previousConfiguration = null;
        self::$previousDatabaseDriver = null;
        self::$previousDatabaseType = null;
        self::$previousTablePrefix = null;
        self::$hasBackedUpGlobalState = false;
    }
}

/**
 * A browser that sends requests through an HttpKernelInterface
 * instead of making actual HTTP requests.
 */
class HttpKernelBrowser extends AbstractBrowser
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        array $server = [],
        ?\Symfony\Component\BrowserKit\History $history = null,
        ?\Symfony\Component\BrowserKit\CookieJar $cookieJar = null,
    ) {
        parent::__construct($server, $history, $cookieJar);
    }

    protected function doRequest(object $request): object
    {
        if (!$request instanceof BrowserKitRequest) {
            throw new \InvalidArgumentException('Expected a Symfony BrowserKit request object.');
        }

        $kernelRequest = Request::create(
            $request->getUri(),
            $request->getMethod(),
            $request->getParameters(),
            $request->getCookies(),
            $request->getFiles(),
            $request->getServer(),
            $request->getContent(),
        );

        return $this->kernel->handle($kernelRequest);
    }

    protected function filterResponse(object $response): BrowserKitResponse
    {
        if (!$response instanceof Response) {
            throw new \InvalidArgumentException('Expected a Symfony HttpFoundation response object.');
        }

        return new BrowserKitResponse(
            (string) $response->getContent(),
            $response->getStatusCode(),
            $response->headers->allPreserveCaseWithoutCookies(),
        );
    }
}
