<?php

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\AbstractController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(ControllerContainerListener::class)]
#[UsesClass(AbstractController::class)]
final class ControllerContainerListenerTest extends TestCase
{
    public function testOnKernelControllerInjectsContainerIntoAbstractController(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $controller = new ListenerTestController();
        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            [$controller, 'index'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener = new ControllerContainerListener($container);
        $listener->onKernelController($event);

        self::assertSame($container, $controller->getInjectedContainer());
        self::assertSame(1, $controller->initializeCalls);
    }

    public function testOnKernelControllerIgnoresNonAbstractControllers(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $controller = new class {
            public function index(): string
            {
                return 'ok';
            }
        };

        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            [$controller, 'index'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener = new ControllerContainerListener($container);
        $listener->onKernelController($event);

        self::assertTrue(true);
    }
}

final class ListenerTestController extends AbstractController
{
    public int $initializeCalls = 0;

    public function __construct()
    {
    }

    protected function initializeFromContainer(): void
    {
        ++$this->initializeCalls;
    }

    public function getInjectedContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function index(): string
    {
        return 'ok';
    }
}
