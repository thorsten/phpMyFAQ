<?php

namespace phpMyFAQ\Tenant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantContext::class)]
#[UsesClass(TenantQuotas::class)]
class TenantContextTest extends TestCase
{
    public function testReturnsConfiguredValues(): void
    {
        $quotas = new TenantQuotas(10, 500, 5, 200, 7);

        $context = new TenantContext(
            tenantId: 42,
            hostname: 'acme.example.com',
            tablePrefix: 'acme_',
            configDir: '/tmp/acme-config',
            plan: 'pro',
            quotas: $quotas,
        );

        $this->assertSame(42, $context->getTenantId());
        $this->assertSame('acme.example.com', $context->getHostname());
        $this->assertSame('acme_', $context->getTablePrefix());
        $this->assertSame('/tmp/acme-config', $context->getConfigDir());
        $this->assertSame('pro', $context->getPlan());
        $this->assertSame($quotas, $context->getQuotas());
    }
}
