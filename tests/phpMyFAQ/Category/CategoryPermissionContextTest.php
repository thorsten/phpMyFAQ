<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use PHPUnit\Framework\TestCase;

class CategoryPermissionContextTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $context = new CategoryPermissionContext();

        $this->assertSame(-1, $context->getUser());
        $this->assertSame([-1], $context->getGroups());
    }

    public function testConstructorWithGroups(): void
    {
        $context = new CategoryPermissionContext([1, 2, 3]);

        $this->assertSame([1, 2, 3], $context->getGroups());
        $this->assertSame(-1, $context->getUser());
    }

    public function testConstructorWithGroupsAndUser(): void
    {
        $context = new CategoryPermissionContext([1, 2, 3], 42);

        $this->assertSame([1, 2, 3], $context->getGroups());
        $this->assertSame(42, $context->getUser());
    }

    public function testConstructorWithEmptyGroupsDefaultsToMinusOne(): void
    {
        $context = new CategoryPermissionContext([]);

        $this->assertSame([-1], $context->getGroups());
    }

    public function testSetAndGetUser(): void
    {
        $context = new CategoryPermissionContext();

        $context->setUser(123);
        $this->assertSame(123, $context->getUser());
    }

    public function testSetAndGetGroups(): void
    {
        $context = new CategoryPermissionContext();

        $context->setGroups([5, 10, 15]);
        $this->assertSame([5, 10, 15], $context->getGroups());
    }

    public function testSetGroupsWithEmptyArrayDefaultsToMinusOne(): void
    {
        $context = new CategoryPermissionContext([1, 2, 3]);

        $context->setGroups([]);
        $this->assertSame([-1], $context->getGroups());
    }

    public function testSetAndGetOwner(): void
    {
        $context = new CategoryPermissionContext();

        $context->setOwner(10, 42);
        $this->assertSame(42, $context->getOwner(10));
    }

    public function testGetOwnerReturnsDefaultForNonExistentCategory(): void
    {
        $context = new CategoryPermissionContext();

        $this->assertSame(1, $context->getOwner(999));
    }

    public function testGetOwnerWithNullReturnsDefault(): void
    {
        $context = new CategoryPermissionContext();

        $this->assertSame(1, $context->getOwner(null));
    }

    public function testSetAndGetModerator(): void
    {
        $context = new CategoryPermissionContext();

        $context->setModerator(10, 5);
        $this->assertSame(5, $context->getModeratorGroupId(10));
    }

    public function testGetModeratorGroupIdReturnsZeroForNonExistentCategory(): void
    {
        $context = new CategoryPermissionContext();

        $this->assertSame(0, $context->getModeratorGroupId(999));
    }

    public function testMultipleOwnersForDifferentCategories(): void
    {
        $context = new CategoryPermissionContext();

        $context->setOwner(1, 10);
        $context->setOwner(2, 20);
        $context->setOwner(3, 30);

        $this->assertSame(10, $context->getOwner(1));
        $this->assertSame(20, $context->getOwner(2));
        $this->assertSame(30, $context->getOwner(3));
    }

    public function testMultipleModeratorsForDifferentCategories(): void
    {
        $context = new CategoryPermissionContext();

        $context->setModerator(1, 100);
        $context->setModerator(2, 200);
        $context->setModerator(3, 300);

        $this->assertSame(100, $context->getModeratorGroupId(1));
        $this->assertSame(200, $context->getModeratorGroupId(2));
        $this->assertSame(300, $context->getModeratorGroupId(3));
    }

    public function testClear(): void
    {
        $context = new CategoryPermissionContext([1, 2, 3], 42);
        $context->setOwner(1, 10);
        $context->setOwner(2, 20);
        $context->setModerator(1, 100);
        $context->setModerator(2, 200);

        $context->clear();

        $this->assertSame(-1, $context->getUser());
        $this->assertSame([-1], $context->getGroups());
        $this->assertSame(1, $context->getOwner(1));
        $this->assertSame(1, $context->getOwner(2));
        $this->assertSame(0, $context->getModeratorGroupId(1));
        $this->assertSame(0, $context->getModeratorGroupId(2));
    }

    public function testOverwriteOwner(): void
    {
        $context = new CategoryPermissionContext();

        $context->setOwner(1, 10);
        $this->assertSame(10, $context->getOwner(1));

        $context->setOwner(1, 20);
        $this->assertSame(20, $context->getOwner(1));
    }

    public function testOverwriteModerator(): void
    {
        $context = new CategoryPermissionContext();

        $context->setModerator(1, 100);
        $this->assertSame(100, $context->getModeratorGroupId(1));

        $context->setModerator(1, 200);
        $this->assertSame(200, $context->getModeratorGroupId(1));
    }
}

