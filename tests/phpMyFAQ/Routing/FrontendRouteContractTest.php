<?php

declare(strict_types=1);

namespace phpMyFAQ\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

/**
 * Matches the URLs and HTTP methods the TypeScript frontend really sends against the real route
 * collection. Controller tests call the action methods directly and cannot see a wrong route path
 * or method.
 *
 * The path is the path info below the entry point: the frontend's "api/bookmark/delete" reaches
 * phpmyfaq/api/index.php as "/bookmark/delete".
 */
#[CoversClass(RouteCollectionBuilder::class)]
#[UsesClass(AttributeRouteLoader::class)]
final class FrontendRouteContractTest extends TestCase
{
    #[DataProvider('frontendRequestProvider')]
    public function testFrontendRequestMatchesRoute(
        string $context,
        string $method,
        string $path,
        string $expectedRoute,
    ): void {
        $routes = new RouteCollectionBuilder()->build($context);
        $matcher = new UrlMatcher($routes, new RequestContext(method: $method));

        self::assertSame($expectedRoute, $matcher->match($path)['_route']);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function frontendRequestProvider(): iterable
    {
        // phpmyfaq/assets/src/api/bookmarks.ts
        yield 'bookmark create' => ['api', 'POST', '/bookmark/create', 'api.private.bookmark.create'];
        yield 'bookmark delete' => ['api', 'DELETE', '/bookmark/delete', 'api.private.bookmark.delete'];
        yield 'bookmark delete all' => ['api', 'DELETE', '/bookmark/delete-all', 'api.private.bookmark.delete-all'];

        // phpmyfaq/admin/assets/src/api/forms.ts
        yield 'forms activate' => ['admin-api', 'PUT', '/forms/activate', 'admin.api.forms.activate'];
        yield 'forms required' => ['admin-api', 'PUT', '/forms/required', 'admin.api.forms.required'];
        yield 'forms translation edit' => [
            'admin-api',
            'PUT',
            '/forms/translation-edit',
            'admin.api.forms.translation-edit',
        ];
        yield 'forms translation delete' => [
            'admin-api',
            'DELETE',
            '/forms/translation-delete',
            'admin.api.forms.translation-delete',
        ];

        // phpmyfaq/admin/assets/src/api/glossary.ts
        yield 'glossary fetch' => ['admin-api', 'GET', '/glossary/1/en', 'admin.api.glossary'];
        yield 'glossary delete' => ['admin-api', 'DELETE', '/glossary/delete', 'admin.api.glossary.delete'];
    }

    public function testGlossaryRouteProvidesTheAttributesTheHandlerReads(): void
    {
        $routes = new RouteCollectionBuilder()->build('admin-api');
        $parameters = new UrlMatcher($routes, new RequestContext(method: 'GET'))->match('/glossary/42/de');

        self::assertSame('42', $parameters['glossaryId']);
        self::assertSame('de', $parameters['glossaryLanguage']);
    }

    #[DataProvider('misplacedPathProvider')]
    public function testEntryPointPrefixIsNotPartOfTheRoutePath(string $context, string $method, string $path): void
    {
        $routes = new RouteCollectionBuilder()->build($context);
        $matcher = new UrlMatcher($routes, new RequestContext(method: $method));

        $this->expectException(ExceptionInterface::class);

        $matcher->match($path);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function misplacedPathProvider(): iterable
    {
        yield 'bookmark delete' => ['api', 'DELETE', '/api/bookmark/delete'];
        yield 'bookmark delete all' => ['api', 'DELETE', '/api/bookmark/delete-all'];
        yield 'forms translation delete' => ['admin-api', 'DELETE', '/admin/api/forms/translation-delete'];
    }
}
