<?php

namespace phpMyFAQ\Container;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerCacheManagerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = PMF_TEST_DIR . '/container-cache-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $cacheFiles = glob($this->cacheDir . '/*.php');
        if ($cacheFiles !== false) {
            array_map(unlink(...), $cacheFiles);
        }

        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }

        parent::tearDown();
    }

    private function buildableContainerFactory(): callable
    {
        return static function (): ContainerBuilder {
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->register('test.service', ArrayObject::class);

            return $containerBuilder;
        };
    }

    public function testCompilesAndDumpsContainerOnFirstCall(): void
    {
        $containerCacheManager = new ContainerCacheManager($this->cacheDir);

        $container = $containerCacheManager->getContainer($this->buildableContainerFactory());

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($container->has('test.service'));
        $this->assertInstanceOf(ArrayObject::class, $container->get('test.service'));
        $this->assertNotEmpty(glob($this->cacheDir . '/*.php'));
    }

    public function testLoadsDumpedContainerWithoutRebuildingOnSecondCall(): void
    {
        new ContainerCacheManager($this->cacheDir)->getContainer($this->buildableContainerFactory());

        $factoryCalls = 0;
        $container = new ContainerCacheManager($this->cacheDir)->getContainer(
            function () use (&$factoryCalls): ContainerBuilder {
                ++$factoryCalls;

                return new ContainerBuilder();
            },
        );

        $this->assertSame(0, $factoryCalls);
        $this->assertTrue($container->has('test.service'));
        $this->assertInstanceOf(ArrayObject::class, $container->get('test.service'));
    }

    public function testFallsBackToPlainContainerBuilderWhenCompilationFails(): void
    {
        $factoryCalls = 0;
        $failingFactory = function () use (&$factoryCalls): ContainerBuilder {
            ++$factoryCalls;
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->register('working.service', ArrayObject::class)->setPublic(true);
            // An alias to a missing service makes compilation fail.
            $containerBuilder->setAlias('broken.alias', 'missing.service');

            return $containerBuilder;
        };

        $loggedErrors = [];
        $containerCacheManager = new ContainerCacheManager(
            $this->cacheDir,
            function (string $message) use (&$loggedErrors): void {
                $loggedErrors[] = $message;
            },
        );

        $container = $containerCacheManager->getContainer($failingFactory);

        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertSame(2, $factoryCalls);
        $this->assertInstanceOf(ArrayObject::class, $container->get('working.service'));
        $this->assertSame([], glob($this->cacheDir . '/*.php'));
        $this->assertCount(1, $loggedErrors);
        $this->assertStringContainsString('cannot compile the DI container', $loggedErrors[0]);
    }
}
