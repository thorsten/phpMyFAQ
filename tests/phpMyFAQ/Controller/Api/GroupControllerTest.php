<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GroupControllerTest extends TestCase
{
    public function testListRequiresAuthentication(): void
    {
        $controller = new GroupController();

        $this->expectException(Exception::class);
        $controller->list();
    }
}
