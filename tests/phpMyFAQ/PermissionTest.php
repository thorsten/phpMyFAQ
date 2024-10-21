<?php

namespace phpMyFAQ;

use InvalidArgumentException;
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

        $basicPermission = Permission::createPermission('basic', $mockConfig);
        $this->assertInstanceOf(BasicPermission::class, $basicPermission);

        $mediumPermission = Permission::createPermission('medium', $mockConfig);
        $this->assertInstanceOf(MediumPermission::class, $mediumPermission);
    }

    /**
     * @throws Exception
     */
    public function testSelectPermClassDoesNotExist(): void
    {
        $mockConfig = $this->createMock(Configuration::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid permission level: nonexistent');
        Permission::createPermission('nonexistent', $mockConfig);
    }
}
