<?php

namespace phpMyFAQ\Tenant;

use phpMyFAQ\Database;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(TenantContextResolver::class)]
#[UsesClass(Database::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantQuotas::class)]
class TenantContextResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('PMF_TENANT_ID');
        putenv('PMF_TENANT_PLAN');
        putenv('PMF_TENANT_QUOTA_MAX_FAQS');
        putenv('PMF_TENANT_QUOTA_MAX_ATTACHMENT_SIZE');
        putenv('PMF_TENANT_QUOTA_MAX_USERS');
        putenv('PMF_TENANT_QUOTA_MAX_API_REQUESTS');
        putenv('PMF_TENANT_QUOTA_MAX_CATEGORIES');

        Database::setTablePrefix('');
    }

    public function testResolveUsesRequestAndEnvironmentValues(): void
    {
        putenv('PMF_TENANT_ID=17');
        putenv('PMF_TENANT_PLAN=enterprise');
        putenv('PMF_TENANT_QUOTA_MAX_FAQS=500');
        putenv('PMF_TENANT_QUOTA_MAX_ATTACHMENT_SIZE=1024');
        putenv('PMF_TENANT_QUOTA_MAX_USERS=80');
        putenv('PMF_TENANT_QUOTA_MAX_API_REQUESTS=12000');
        putenv('PMF_TENANT_QUOTA_MAX_CATEGORIES=200');
        Database::setTablePrefix('acme_');

        $resolver = new TenantContextResolver();
        $context = $resolver->resolve(Request::create('https://acme.faq.example.com/dashboard'));

        $this->assertSame(17, $context->getTenantId());
        $this->assertSame('acme.faq.example.com', $context->getHostname());
        $this->assertSame('acme_', $context->getTablePrefix());
        $this->assertSame(PMF_CONFIG_DIR, $context->getConfigDir());
        $this->assertSame('enterprise', $context->getPlan());
        $this->assertSame(500, $context->getQuotas()->getMaxFaqs());
        $this->assertSame(1024, $context->getQuotas()->getMaxAttachmentSize());
        $this->assertSame(80, $context->getQuotas()->getMaxUsers());
        $this->assertSame(12000, $context->getQuotas()->getMaxApiRequests());
        $this->assertSame(200, $context->getQuotas()->getMaxCategories());
    }

    /**
     * @throws Exception
     */
    public function testResolveFallsBackToDefaultsWhenEnvironmentIsMissing(): void
    {
        Database::setTablePrefix('');

        $request = $this->createStub(Request::class);
        $request->method('getHost')->willReturn('');

        $resolver = new TenantContextResolver();
        $context = $resolver->resolve($request);

        $this->assertSame(0, $context->getTenantId());
        $this->assertSame('localhost', $context->getHostname());
        $this->assertSame('', $context->getTablePrefix());
        $this->assertSame(PMF_CONFIG_DIR, $context->getConfigDir());
        $this->assertSame('free', $context->getPlan());
        $this->assertNull($context->getQuotas()->getMaxFaqs());
        $this->assertNull($context->getQuotas()->getMaxAttachmentSize());
        $this->assertNull($context->getQuotas()->getMaxUsers());
        $this->assertNull($context->getQuotas()->getMaxApiRequests());
        $this->assertNull($context->getQuotas()->getMaxCategories());
    }
}
