<?php

namespace phpMyFAQ\Tenant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantQuotas::class)]
class TenantQuotasTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $quotas = new TenantQuotas();

        $this->assertNull($quotas->getMaxFaqs());
        $this->assertNull($quotas->getMaxAttachmentSize());
        $this->assertNull($quotas->getMaxUsers());
        $this->assertNull($quotas->getMaxApiRequests());
        $this->assertNull($quotas->getMaxCategories());
    }

    public function testReturnsConfiguredValues(): void
    {
        $quotas = new TenantQuotas(100, 2048, 25, 1000, 50);

        $this->assertSame(100, $quotas->getMaxFaqs());
        $this->assertSame(2048, $quotas->getMaxAttachmentSize());
        $this->assertSame(25, $quotas->getMaxUsers());
        $this->assertSame(1000, $quotas->getMaxApiRequests());
        $this->assertSame(50, $quotas->getMaxCategories());
    }
}
