<?php

declare(strict_types=1);

namespace phpMyFAQ\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversClass(ContainerRegistry::class)]
final class ContainerRegistryTest extends TestCase
{
    private ?ContainerInterface $previousContainer = null;

    protected function setUp(): void
    {
        $this->previousContainer = ContainerRegistry::get();
        ContainerRegistry::reset();
    }

    protected function tearDown(): void
    {
        if ($this->previousContainer instanceof ContainerInterface) {
            ContainerRegistry::set($this->previousContainer);
        } else {
            ContainerRegistry::reset();
        }
    }

    public function testGetReturnsNullWhenNothingIsRegistered(): void
    {
        $this->assertNull(ContainerRegistry::get());
    }

    public function testSetMakesContainerGloballyAvailable(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        ContainerRegistry::set($container);

        $this->assertSame($container, ContainerRegistry::get());
    }

    public function testSetReplacesPreviousContainer(): void
    {
        $first = $this->createStub(ContainerInterface::class);
        $second = $this->createStub(ContainerInterface::class);

        ContainerRegistry::set($first);
        ContainerRegistry::set($second);

        $this->assertSame($second, ContainerRegistry::get());
    }

    public function testResetClearsContainer(): void
    {
        ContainerRegistry::set($this->createStub(ContainerInterface::class));

        ContainerRegistry::reset();

        $this->assertNull(ContainerRegistry::get());
    }
}
