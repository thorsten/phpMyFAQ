<?php

namespace phpMyFAQ\Routing;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCacheManagerTest extends TestCase
{
    private string $cacheDir;
    private RouteCacheManager $cacheManager;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpmyfaq_route_cache_test_' . uniqid();
        $this->cacheManager = new RouteCacheManager($this->cacheDir, false);
    }

    protected function tearDown(): void
    {
        // Clean up cache directory
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    public function testConstructorCreatesCacheDirectory(): void
    {
        $this->assertTrue(is_dir($this->cacheDir));
    }

    public function testGetRoutesCallsLoaderWhenCacheDoesNotExist(): void
    {
        $wasCalled = false;
        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add('test.route', new Route('/test'));

        $loader = function () use (&$wasCalled, $expectedRoutes) {
            $wasCalled = true;
            return $expectedRoutes;
        };

        $routes = $this->cacheManager->getRoutes('public', $loader);

        $this->assertTrue($wasCalled);
        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertEquals(1, $routes->count());
    }

    public function testGetRoutesWritesCacheInProductionMode(): void
    {
        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add('test.route', new Route('/test'));

        $loader = function () use ($expectedRoutes) {
            return $expectedRoutes;
        };

        $this->cacheManager->getRoutes('public', $loader);

        // Check that a cache file was created
        $this->assertTrue($this->cacheManager->hasCache('public'));
    }

    public function testGetRoutesDoesNotWriteCacheInDebugMode(): void
    {
        $debugCacheManager = new RouteCacheManager($this->cacheDir, true);

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add('test.route', new Route('/test'));

        $loader = function () use ($expectedRoutes) {
            return $expectedRoutes;
        };

        $debugCacheManager->getRoutes('public', $loader);

        // Cache should not be written in debug mode
        $this->assertFalse($debugCacheManager->hasCache('public'));
    }

    public function testGetRoutesReadsFromCacheWhenAvailable(): void
    {
        $routes1 = new RouteCollection();
        $routes1->add('test.route', new Route('/test'));

        // First call to populate the cache
        $loader = function () use ($routes1) {
            return $routes1;
        };

        $this->cacheManager->getRoutes('public', $loader);

        // The second call should read from the cache
        $loaderCallCount = 0;
        $loader2 = function () use (&$loaderCallCount) {
            $loaderCallCount++;
            $routes = new RouteCollection();
            $routes->add('other.route', new Route('/other'));
            return $routes;
        };

        $cachedRoutes = $this->cacheManager->getRoutes('public', $loader2);

        // Loader should not be called (reading from cache)
        $this->assertEquals(0, $loaderCallCount);
        $this->assertInstanceOf(RouteCollection::class, $cachedRoutes);
        $this->assertEquals(1, $cachedRoutes->count());
    }

    public function testClearRemovesAllCacheFiles(): void
    {
        $routes = new RouteCollection();
        $routes->add('test.route', new Route('/test'));

        $loader = function () use ($routes) {
            return $routes;
        };

        // Create cache for multiple contexts
        $this->cacheManager->getRoutes('public', $loader);
        $this->cacheManager->getRoutes('admin', $loader);

        $this->assertTrue($this->cacheManager->hasCache('public'));
        $this->assertTrue($this->cacheManager->hasCache('admin'));

        // Clear all caches
        $this->cacheManager->clear();

        $this->assertFalse($this->cacheManager->hasCache('public'));
        $this->assertFalse($this->cacheManager->hasCache('admin'));
    }

    public function testClearContextRemovesSpecificCache(): void
    {
        $routes = new RouteCollection();
        $routes->add('test.route', new Route('/test'));

        $loader = function () use ($routes) {
            return $routes;
        };

        // Create cache for multiple contexts
        $this->cacheManager->getRoutes('public', $loader);
        $this->cacheManager->getRoutes('admin', $loader);

        $this->assertTrue($this->cacheManager->hasCache('public'));
        $this->assertTrue($this->cacheManager->hasCache('admin'));

        // Clear only public context
        $this->cacheManager->clearContext('public');

        $this->assertFalse($this->cacheManager->hasCache('public'));
        $this->assertTrue($this->cacheManager->hasCache('admin'));
    }

    public function testHasCacheReturnsFalseForNonExistentCache(): void
    {
        $this->assertFalse($this->cacheManager->hasCache('nonexistent'));
    }

    /**
     * @throws Exception
     */
    public function testCachedRoutesCanBeRead(): void
    {
        $originalRoutes = new RouteCollection();
        $originalRoutes->add(
            'test.route',
            new Route(
                path: '/test/{id}',
                defaults: ['_controller' => 'TestController::index'],
                requirements: ['id' => '\d+'],
                methods: ['GET', 'POST'],
            ),
        );

        $loader = function () use ($originalRoutes) {
            return $originalRoutes;
        };

        $this->cacheManager->getRoutes('public', $loader);

        // Read from cache (second call)
        $cachedRoutes = $this->cacheManager->getRoutes(
            /**
             * @throws Exception
             */ 'public',
            function () {
                throw new Exception('Loader should not be called when reading from cache');
            },
        );

        $this->assertInstanceOf(RouteCollection::class, $cachedRoutes);
        $this->assertEquals(1, $cachedRoutes->count());

        $route = $cachedRoutes->get('test.route');
        $this->assertNotNull($route);
        $this->assertEquals('/test/{id}', $route->getPath());
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
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
