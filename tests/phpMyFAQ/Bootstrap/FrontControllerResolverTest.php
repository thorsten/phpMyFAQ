<?php

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FrontControllerResolver::class)]
final class FrontControllerResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function requestUriProvider(): iterable
    {
        yield 'start page' => ['/', 'index.php'];
        yield 'public route' => ['/content/42/en/some-faq.html?lang=en', 'index.php'];
        yield 'update route' => ['/update/', 'index.php'];
        yield 'admin root without slash' => ['/admin', 'admin/index.php'];
        yield 'admin route' => ['/admin/faqs?page=2', 'admin/index.php'];
        yield 'admin api' => ['/admin/api/faq/create', 'admin/api/index.php'];
        yield 'admin api root' => ['/admin/api/', 'admin/api/index.php'];
        yield 'public api' => ['/api/v3.1/search?q=test', 'api/index.php'];
        yield 'setup' => ['/setup/index.php', 'setup/index.php'];
        yield 'setup route' => ['/setup/check', 'setup/index.php'];
        yield 'direct php script' => ['/services/azure/callback.php?code=abc', 'services/azure/callback.php'];
        yield 'admin lookalike' => ['/administration-tips.html', 'index.php'];
        yield 'api lookalike' => ['/apidocs.html', 'index.php'];
    }

    #[DataProvider('requestUriProvider')]
    public function testResolvesTheFrontControllerLikeTheCaddyfileRewrites(string $requestUri, string $expected): void
    {
        $this->assertSame($expected, FrontControllerResolver::resolve($requestUri));
    }

    public function testRejectsDirectoryTraversalInPhpScriptPaths(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FrontControllerResolver::resolve('/services/../../etc/passwd.php');
    }

    public function testDoesNotServeTheWorkerScriptItself(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FrontControllerResolver::resolve('/frankenphp-worker.php');
    }
}
