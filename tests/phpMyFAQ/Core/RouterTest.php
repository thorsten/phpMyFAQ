<?php

/**
 * Router Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-11-18
 */

use phpMyFAQ\Core\Router;
use PHPUnit\Framework\TestCase;

/**
 * Class RouterTest
 */
class RouterTest extends TestCase
{
    /** @var Router */
    protected $router;

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
