<?php

/**
 * Controller Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-11-18
 */

use phpMyFAQ\System;
use phpMyFAQ\Core\Controller;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 */
class ControllerTest extends TestCase
{
    protected $ControllerFromAbstract;

    protected function setUp(): void
    {
        $this->ControllerFromAbstract = new class (['api/version']) extends Controller {
            public function versionAction()
            {
                return System::getVersion();
            }
        };
    }

    public function testAbstractClassMethod()
    {
        $this->assertEquals(
            System::getVersion(),
            $this->ControllerFromAbstract->versionAction()
        );
    }
}
