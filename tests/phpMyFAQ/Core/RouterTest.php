<?php

namespace phpMyFAQ\Core;

use PHPUnit\Framework\TestCase;

/**
 * Class RouterTest
 */
class RouterTest extends TestCase
{
    /** @var Router */
    protected Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testAdd(): void
    {
        $this->router->add('api/version', ['controller' => 'Api', 'action' => 'version']);

        $this->assertEquals(
            ['/^api\/version$/i' => ['controller' => 'Api', 'action' => 'version']],
            $this->router->getRoutes()
        );
    }

    public function testMatch(): void
    {
        $this->router->add('api/version', ['controller' => 'Api', 'action' => 'version']);

        $this->assertTrue($this->router->match('api/version'));
        $this->assertEquals(['controller' => 'Api', 'action' => 'version'], $this->router->getParameters());
    }
}
