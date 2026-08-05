<?php

declare(strict_types=1);

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

class AdminRoutesTest extends TestCase
{
    private static RouteCollection $routes;

    public static function setUpBeforeClass(): void
    {
        self::$routes = include PMF_SRC_DIR . '/admin-routes.php';
    }

    /**
     * The revision selector in the FAQ editor submits a POST request to this
     * route, so it must accept both GET and POST (see issue #4547).
     */
    public function testFaqEditRouteAcceptsGetAndPost(): void
    {
        $route = self::$routes->get('admin.faq.edit');

        static::assertNotNull($route);
        static::assertEqualsCanonicalizing(['GET', 'POST'], $route->getMethods());
    }

    public function testAllRoutesDeclareValidHttpMethods(): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach (self::$routes as $name => $route) {
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
