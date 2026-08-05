<?php

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Routing\RouteCollectionBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

#[AllowMockObjectsWithoutExpectations]
class AdminRoutesTest extends TestCase
{
    private RouteCollection $routes;

    protected function setUp(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->willReturn(false);

        $this->routes = new RouteCollectionBuilder($configuration)->build('admin', true);
    }

    /**
     * The revision selector in the FAQ editor submits a POST request to this
     * route, so it must accept both GET and POST (see issue #4547).
     */
    public function testFaqEditRouteAcceptsGetAndPost(): void
    {
        $route = $this->routes->get('admin.faq.edit');

        static::assertNotNull($route);
        static::assertEqualsCanonicalizing(['GET', 'POST'], $route->getMethods());
    }

    public function testAllRoutesDeclareValidHttpMethods(): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($this->routes as $name => $route) {
            static::assertNotEmpty($route->getMethods(), sprintf('Route "%s" has no HTTP methods', $name));

            foreach ($route->getMethods() as $method) {
                static::assertContains(
                    $method,
                    $validMethods,
                    sprintf('Route "%s" declares an invalid HTTP method "%s"', $name, $method),
                );
            }
        }
    }
}
