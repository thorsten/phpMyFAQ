<?php

namespace phpMyFAQ\Routing;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Routing\Route;
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

    public function testGetClassFromFileReturnsNullWhenNamespaceOrClassIsMissing(): void
    {
        $this->assertNull($this->withoutWarnings(fn(): mixed => $this->invokeLoaderMethod(
            'getClassFromFile',
            $this->fixturesDir . '/MissingFile.php',
        )));

        $file = $this->fixturesDir . '/PlainFile.php';
        file_put_contents($file, "<?php\n\nreturn true;\n");

        $this->assertNull($this->invokeLoaderMethod('getClassFromFile', $file));

        $namespaceOnlyFile = $this->fixturesDir . '/NamespaceOnly.php';
        file_put_contents($namespaceOnlyFile, "<?php\n\nnamespace phpMyFAQ\\Routing\\Fixtures;\n");
        $this->assertNull($this->invokeLoaderMethod('getClassFromFile', $namespaceOnlyFile));

        $classOnlyFile = $this->fixturesDir . '/ClassOnly.php';
        file_put_contents($classOnlyFile, "<?php\n\nclass ClassOnly {}\n");
        $this->assertNull($this->invokeLoaderMethod('getClassFromFile', $classOnlyFile));
    }

    public function testMatchesContextHandlesAllSupportedContextsAndUnnamedRoutes(): void
    {
        $publicRoute = new Route('/public', ['_route' => 'public.example']);
        $adminRoute = new Route('/admin', ['_route' => 'admin.example']);
        $adminApiRoute = new Route('/admin-api', ['_route' => 'admin.api.example']);
        $apiRoute = new Route('/api', ['_route' => 'api.example']);
        $unnamedRoute = new Route('/unnamed');

        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $publicRoute, 'public'));
        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $adminRoute, 'admin'));
        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $adminApiRoute, 'admin-api'));
        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $apiRoute, 'api'));
        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $publicRoute, 'unknown'));
        $this->assertTrue($this->invokeLoaderMethod('matchesContext', $unnamedRoute, 'admin'));

        $this->assertFalse($this->invokeLoaderMethod('matchesContext', $adminApiRoute, 'admin'));
        $this->assertFalse($this->invokeLoaderMethod('matchesContext', $adminApiRoute, 'api'));
        $this->assertFalse($this->invokeLoaderMethod('matchesContext', $publicRoute, 'api'));
    }

    public function testLoadHandlesUnnamedLocalizedAndInvalidRouteAttributes(): void
    {
        $controllerCode = <<<'PHP'
            <?php

            namespace phpMyFAQ\Routing\Fixtures;

            use Symfony\Component\Routing\Attribute\Route;

            class EdgeCaseController
            {
                #[Route(path: '/unnamed', methods: ['GET'])]
                public function unnamed(): void
                {
                }

                #[Route(path: ['en' => '/localized', 'de' => '/lokalisiert'], name: 'public.localized')]
                public function localized(): void
                {
                }

                #[Route(path: '/invalid/{id}', name: 'public.invalid', requirements: ['id' => ''])]
                public function invalid(): void
                {
                }
            }
            PHP;

        $edgeCaseDir = $this->fixturesDir . '/EdgeCase';
        mkdir($edgeCaseDir, 0755, true);
        file_put_contents($edgeCaseDir . '/EdgeCaseController.php', $controllerCode);
        require_once $edgeCaseDir . '/EdgeCaseController.php';

        $routes = $this->loader->load($edgeCaseDir, 'public');

        $expectedName = 'route_' . md5('phpMyFAQ\Routing\Fixtures\EdgeCaseController' . 'unnamed');
        $this->assertSame([$expectedName], array_keys($routes->all()));
        $this->assertSame('/unnamed', $routes->get($expectedName)?->getPath());
        $this->assertNull($routes->get('public.localized'));
        $this->assertNull($routes->get('public.invalid'));
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

    private function invokeLoaderMethod(string $methodName, mixed ...$arguments): mixed
    {
        $method = new ReflectionMethod($this->loader, $methodName);

        return $method->invoke($this->loader, ...$arguments);
    }

    private function withoutWarnings(callable $callback): mixed
    {
        set_error_handler(static fn(): bool => true);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
