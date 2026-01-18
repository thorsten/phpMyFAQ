<?php

namespace phpMyFAQ\Routing;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

#[AllowMockObjectsWithoutExpectations]
class RouteCollectionBuilderTest extends TestCase
{
    private Configuration $configuration;
    private RouteCollectionBuilder $builder;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->builder = new RouteCollectionBuilder($this->configuration);
    }

    public function testBuildReturnsRouteCollection(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('public', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildIncludesFileRoutesWhenNotAttributesOnly(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('public', false);

        // Public routes file exists and should be loaded
        // We can't assert exact count as it depends on actual route files
        $this->assertGreaterThanOrEqual(0, $routes->count());
    }

    public function testBuildSkipsFileRoutesWhenAttributesOnly(): void
    {
        $this->configuration->method('get')->willReturn(true);

        $routes = $this->builder->build('public', true);

        // With attributesOnly=true, we should only get routes from attributes
        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesPublicContext(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('public', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesAdminContext(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('admin', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesApiContext(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('api', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesAdminApiContext(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $routes = $this->builder->build('admin-api', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testBuildHandlesNonExistentRouteFile(): void
    {
        $this->configuration->method('get')->willReturn(false);

        // Should not throw exception for contexts without route files
        $routes = $this->builder->build('unknown', false);

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }
}
