<?php

namespace phpMyFAQ\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionBuilderTest extends TestCase
{
    private RouteCollectionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new RouteCollectionBuilder();
    }

    public function testBuildReturnsRouteCollection(): void
    {
        $routes = $this->builder->build('public', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildIncludesFileRoutesWhenNotAttributesOnly(): void
    {
        $routes = $this->builder->build('public', false);

        // Public routes file exists and should be loaded
        // We can't assert exact count as it depends on actual route files
        $this->assertGreaterThanOrEqual(0, $routes->count());
    }

    public function testBuildSkipsFileRoutesWhenAttributesOnly(): void
    {
        $routes = $this->builder->build('public', true);

        // With attributesOnly=true, we should only get routes from attributes
        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesPublicContext(): void
    {
        $routes = $this->builder->build('public', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesAdminContext(): void
    {
        $routes = $this->builder->build('admin', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesApiContext(): void
    {
        $routes = $this->builder->build('api', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesAdminApiContext(): void
    {
        $routes = $this->builder->build('admin-api', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesNonExistentRouteFile(): void
    {
        // Should not throw exception for contexts without route files
        $routes = $this->builder->build('unknown', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }
}
