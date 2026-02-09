<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantIsolationMode::class)]
class TenantIsolationModeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('prefix', TenantIsolationMode::PREFIX->value);
        $this->assertEquals('schema', TenantIsolationMode::SCHEMA->value);
        $this->assertEquals('database', TenantIsolationMode::DATABASE->value);
    }

    public function testTryFromValidValues(): void
    {
        $this->assertEquals(TenantIsolationMode::PREFIX, TenantIsolationMode::tryFrom('prefix'));
        $this->assertEquals(TenantIsolationMode::SCHEMA, TenantIsolationMode::tryFrom('schema'));
        $this->assertEquals(TenantIsolationMode::DATABASE, TenantIsolationMode::tryFrom('database'));
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(TenantIsolationMode::tryFrom('invalid'));
    }
}
