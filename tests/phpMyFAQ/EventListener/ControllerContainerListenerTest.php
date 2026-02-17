<?php

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\AbstractController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerContainerListenerTest extends TestCase
{
    public function testInjectsContainerIntoAbstractController(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $listener = new ControllerContainerListener($container);

        // Create a concrete anonymous class extending AbstractController
        // that tracks setContainer calls without triggering initializeFromContainer
        $controller = new class() extends AbstractController {
            public bool $containerWasSet = false;

            public function __construct()
            {
                // Skip parent constructor to avoid container creation in tests
            }

            public function setContainer(ContainerInterface $container): void
            {
                $this->containerWasSet = true;
                // Don't call parent::setContainer() to avoid needing full container setup
                $this->container = $container;
            }

            public function testAction(): Response
            {
                return new Response('test');
            }
        };

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');

        $event = new ControllerEvent($kernel, [$controller, 'testAction'], $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelController($event);

        $this->assertTrue($controller->containerWasSet);
    }

    public function testIgnoresNonAbstractControllers(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $listener = new ControllerContainerListener($container);

        $controller = function () {
            return new Response('test');
        };

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should not throw or error
        $listener->onKernelController($event);
        $this->assertTrue(true);
    }
}
