<?php

namespace phpMyFAQ\Core;

use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 */
class ControllerTest extends TestCase
{
    protected Controller $ControllerFromAbstract;

    protected function setUp(): void
    {
        $this->ControllerFromAbstract = new class (['api/version']) extends Controller {
            public function versionAction(): string
            {
                return System::getVersion();
            }
        };
    }

    public function testAbstractClassMethod(): void
    {
        $this->assertEquals(
            System::getVersion(),
            $this->ControllerFromAbstract->versionAction()
        );
    }
}
