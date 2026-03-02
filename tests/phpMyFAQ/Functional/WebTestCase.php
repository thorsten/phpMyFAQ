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

    protected static function createClient(string $routingContext = 'public'): HttpKernelBrowser
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        new Configuration($dbHandle);
        self::initializeDatabaseStatics($dbHandle);

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

    private static function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);

        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');

        Database::setTablePrefix('');
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
