<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(ContainerControllerResolver::class)]
final class ContainerControllerResolverTest extends TestCase
{
    public function testGetControllerReturnsContainerServiceWhenRegistered(): void
    {
        $service = new ResolverServiceController();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with(ResolverServiceController::class)->willReturn(true);
        $container->expects($this->once())->method('get')->with(ResolverServiceController::class)->willReturn($service);

        $resolver = new ContainerControllerResolver($container);
        $request = new Request();
        $request->attributes->set('_controller', ResolverServiceController::class . '::index');

        $controller = $resolver->getController($request);

        self::assertIsArray($controller);
        self::assertSame($service, $controller[0]);
        self::assertSame('index', $controller[1]);
    }

    public function testGetControllerFallsBackToInstantiatingControllerWhenNotRegistered(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with(ResolverPlainController::class)->willReturn(false);
        $container->expects($this->never())->method('get');

        $resolver = new ContainerControllerResolver($container);
        $request = new Request();
        $request->attributes->set('_controller', ResolverPlainController::class . '::show');

        $controller = $resolver->getController($request);

        self::assertIsArray($controller);
        self::assertInstanceOf(ResolverPlainController::class, $controller[0]);
        self::assertSame('show', $controller[1]);
    }

    public function testGetControllerReturnsFalseWhenControllerAttributeIsMissing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('has');
        $container->expects($this->never())->method('get');

        $resolver = new ContainerControllerResolver($container);

        self::assertFalse($resolver->getController(new Request()));
    }
}

final class ResolverServiceController
{
    public function index(): string
    {
        return 'ok';
    }
}

final class ResolverPlainController
{
    public function show(): string
    {
        return 'ok';
    }
}
