<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelTest extends TestCase
{
    public function testKernelImplementsHttpKernelInterface(): void
    {
        $kernel = new Kernel(routingContext: 'public', debug: true);
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
    }

    public function testKernelRoutingContext(): void
    {
        $kernel = new Kernel(routingContext: 'admin', debug: false);
        $this->assertEquals('admin', $kernel->getRoutingContext());
    }

    public function testKernelDebugMode(): void
    {
        $kernel = new Kernel(routingContext: 'public', debug: true);
        $this->assertTrue($kernel->isDebug());
    }

    public function testKernelNonDebugMode(): void
    {
        $kernel = new Kernel(routingContext: 'public', debug: false);
        $this->assertFalse($kernel->isDebug());
    }

    public function testKernelDefaultParameters(): void
    {
        $kernel = new Kernel();
        $this->assertEquals('public', $kernel->getRoutingContext());
        $this->assertFalse($kernel->isDebug());
    }
}
