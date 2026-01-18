<?php

namespace phpMyFAQ\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

class AttributeRouteLoaderTest extends TestCase
{
    private AttributeRouteLoader $loader;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->loader = new AttributeRouteLoader();
        $this->fixturesDir = __DIR__ . '/Fixtures';

        // Create fixtures directory if it doesn't exist
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0755, true);
        }

        // Create a test controller with route attributes
        $this->createTestController();
    }

    protected function tearDown(): void
    {
        // Clean up test fixtures
        if (is_dir($this->fixturesDir)) {
            $this->removeDirectory($this->fixturesDir);
        }
    }

    public function testLoadReturnsEmptyCollectionForNonExistentDirectory(): void
    {
        $routes = $this->loader->load('/non/existent/path', 'public');

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertCount(0, $routes);
    }

    public function testLoadReturnsRouteCollectionWithRoutes(): void
    {
        // Include the fixture file so classes can be found
        require_once $this->fixturesDir . '/TestController.php';

        $routes = $this->loader->load($this->fixturesDir, 'public');

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertGreaterThan(0, $routes->count());
    }

    public function testLoadFindsPublicRoutes(): void
    {
        // Include the fixture file so classes can be found
        require_once $this->fixturesDir . '/TestController.php';

        $routes = $this->loader->load($this->fixturesDir, 'public');

        $this->assertTrue($routes->get('public.test.index') !== null);
    }

    public function testLoadFiltersRoutesByContext(): void
    {
        // Include the fixture file so classes can be found
        require_once $this->fixturesDir . '/TestController.php';

        $publicRoutes = $this->loader->load($this->fixturesDir, 'public');
        $apiRoutes = $this->loader->load($this->fixturesDir, 'api');

        // Public context should only have routes starting with 'public.'
        foreach ($publicRoutes as $name => $route) {
            $routeName = $route->getDefault('_route');
            if ($routeName) {
                $this->assertStringStartsWith('public.', $routeName);
            }
        }

        // API context should only have routes starting with 'api.'
        foreach ($apiRoutes as $name => $route) {
            $routeName = $route->getDefault('_route');
            if ($routeName) {
                $this->assertStringStartsWith('api.', $routeName);
            }
        }
    }

    public function testLoadExtractsRouteMetadata(): void
    {
        // Include the fixture file so classes can be found
        require_once $this->fixturesDir . '/TestController.php';

        $routes = $this->loader->load($this->fixturesDir, 'public');
        $route = $routes->get('public.test.index');

        $this->assertNotNull($route);
        $this->assertEquals('/test', $route->getPath());
        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertArrayHasKey('_controller', $route->getDefaults());
    }

    private function createTestController(): void
    {
        $controllerCode = <<<'PHP'
            <?php

            namespace phpMyFAQ\Routing\Fixtures;

            use Symfony\Component\Routing\Attribute\Route;

            class TestController
            {
                #[Route(path: '/test', name: 'public.test.index', methods: ['GET'])]
                public function index(): void
                {
                }

                #[Route(path: '/api/test', name: 'api.test.show', methods: ['GET'])]
                public function show(): void
                {
                }

                #[Route(path: '/admin/test', name: 'admin.test.list', methods: ['GET'])]
                public function list(): void
                {
                }
            }
            PHP;

        file_put_contents($this->fixturesDir . '/TestController.php', $controllerCode);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
