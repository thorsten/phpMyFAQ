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

use phpMyFAQ\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class WebTestCase extends TestCase
{
    protected static ?Kernel $kernel = null;

    protected static ?HttpKernelBrowser $client = null;

    protected static function createClient(string $routingContext = 'public'): HttpKernelBrowser
    {
        static::$kernel = new PhpMyFaqTestKernel($routingContext);
        static::$kernel->boot();
        static::$client = new HttpKernelBrowser(static::$kernel);

        return static::$client;
    }

    protected static function assertResponseStatusCodeSame(int $expectedStatusCode, ?Response $response = null): void
    {
        $response ??= static::$client?->getResponse();
        static::assertNotNull($response, 'No response available. Did you make a request?');
        static::assertSame($expectedStatusCode, $response->getStatusCode());
    }

    protected static function assertResponseIsSuccessful(?Response $response = null): void
    {
        $response ??= static::$client?->getResponse();
        static::assertNotNull($response, 'No response available. Did you make a request?');
        static::assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d', $response->getStatusCode()),
        );
    }

    protected static function assertResponseHeaderSame(
        string $headerName,
        string $expectedValue,
        ?Response $response = null,
    ): void {
        $response ??= static::$client?->getResponse();
        static::assertNotNull($response, 'No response available.');
        static::assertSame($expectedValue, $response->headers->get($headerName));
    }

    protected function tearDown(): void
    {
        static::$kernel = null;
        static::$client = null;
    }
}

/**
 * A browser that sends requests through an HttpKernelInterface
 * instead of making actual HTTP requests.
 */
class HttpKernelBrowser extends AbstractBrowser
{
    private ?Response $response = null;

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        array $server = [],
        ?\Symfony\Component\BrowserKit\History $history = null,
        ?\Symfony\Component\BrowserKit\CookieJar $cookieJar = null,
    ) {
        parent::__construct($server, $history, $cookieJar);
    }

    protected function doRequest(object $request): Response
    {
        if (!$request instanceof Request) {
            throw new \InvalidArgumentException('Expected a Symfony Request object.');
        }

        $this->response = $this->kernel->handle($request);

        return $this->response;
    }

    public function getResponse(): ?Response
    {
        $response = $this->getInternalResponse();

        // Try the stored response first
        if ($this->response !== null) {
            return $this->response;
        }

        return null;
    }
}
