<?php

namespace phpMyFAQ;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\MediumPermission;

class PermissionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSelectPermReturnsCorrectClass(): void
    {
        $mockConfig = $this->createMock(Configuration::class);

        $basicPermission = Permission::selectPerm('basic', $mockConfig);
        $this->assertInstanceOf(BasicPermission::class, $basicPermission);

        $mediumPermission = Permission::selectPerm('medium', $mockConfig);
        $this->assertInstanceOf(MediumPermission::class, $mediumPermission);

        $invalidPermission = Permission::selectPerm('invalid', $mockConfig);
        $this->assertInstanceOf(Permission::class, $invalidPermission);
    }

    /**
     * @throws Exception
     */
    public function testSelectPermClassDoesNotExist(): void
    {
        $mockConfig = $this->createMock(Configuration::class);

        $invalidPermission = Permission::selectPerm('nonexistent', $mockConfig);

        $this->assertInstanceOf(Permission::class, $invalidPermission);
    }
}
