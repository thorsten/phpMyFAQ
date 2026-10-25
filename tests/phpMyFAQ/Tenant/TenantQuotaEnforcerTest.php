<?php

namespace phpMyFAQ\Tenant;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TenantQuotaEnforcer::class)]
#[CoversClass(QuotaExceededException::class)]
#[UsesClass(Database::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantQuotas::class)]
class TenantQuotaEnforcerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Database::setTablePrefix('');
    }

    public function testAssertCanCreateFaqThrowsWhenQuotaIsExceeded(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(1) AS amount FROM pmf_faqdata')
            ->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => 1]);

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', 'pmf_', PMF_CONFIG_DIR, 'free', new TenantQuotas(1)),
        );

        $this->expectException(QuotaExceededException::class);
        $enforcer->assertCanCreateFaq();
    }

    public function testAssertCanStoreAttachmentThrowsWhenQuotaIsExceeded(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COALESCE(SUM(filesize), 0) AS amount FROM pmf_faqattachment')
            ->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => 800000]);

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', 'pmf_', PMF_CONFIG_DIR, 'free', new TenantQuotas(null, 1)),
        );

        $this->expectException(QuotaExceededException::class);
        $enforcer->assertCanStoreAttachment(300000);
    }

    public function testAssertCanCreateCategoryDoesNothingWhenQuotaIsNotConfigured(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('query');

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', '', PMF_CONFIG_DIR, 'free', new TenantQuotas()),
        );

        $enforcer->assertCanCreateCategory();
        $this->assertTrue(true);
    }

    public function testAssertCanCreateUserThrowsRuntimeExceptionOnQueryFailure(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(1) AS amount FROM pmf_faquser')
            ->willReturn(false);
        $db->expects($this->once())->method('error')->willReturn('db failed');

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(
                1,
                'tenant.example.test',
                'pmf_',
                PMF_CONFIG_DIR,
                'free',
                new TenantQuotas(null, null, 5),
            ),
        );

        $this->expectException(RuntimeException::class);
        $enforcer->assertCanCreateUser();
    }

    public function testAssertCanStoreAttachmentCapsAnOversizedLimitAtPhpIntMax(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => PHP_INT_MAX - 10]);

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(
                1,
                'tenant.example.test',
                '',
                PMF_CONFIG_DIR,
                'free',
                new TenantQuotas(null, PHP_INT_MAX),
            ),
        );

        $enforcer->assertCanStoreAttachment(10);
        $this->assertTrue(true);
    }

    public function testAssertCanStoreAttachmentThrowsRuntimeExceptionOnQueryFailure(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->willReturn(false);
        $db->expects($this->once())->method('error')->willReturn('db failed');

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', '', PMF_CONFIG_DIR, 'free', new TenantQuotas(null, 1)),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to evaluate tenant quota for attachments: db failed');
        $enforcer->assertCanStoreAttachment(1);
    }

    public function testAssertCanCreateFaqTreatsAMissingRowAsZero(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(false);

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', '', PMF_CONFIG_DIR, 'free', new TenantQuotas(1)),
        );

        $enforcer->assertCanCreateFaq();
        $this->assertTrue(true);
    }

    public function testAssertCanCreateFaqTreatsAFalseCountAsZero(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => false]);

        $enforcer = new TenantQuotaEnforcer(
            $db,
            new TenantContext(1, 'tenant.example.test', '', PMF_CONFIG_DIR, 'free', new TenantQuotas(1)),
        );

        $enforcer->assertCanCreateFaq();
        $this->assertTrue(true);
    }
}
