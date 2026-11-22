<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * The methods the administration manages scopes with, against a real database.
 */
#[CoversClass(ScopeRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class ScopeRepositoryStorageTest extends TestCase
{
    use TestDatabaseTrait;

    private ScopeRepository $scopes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scopes = new ScopeRepository($this->createTestConfiguration());
    }

    public function testAnEnabledScopeCanBeRequested(): void
    {
        $this->assertNull($this->scopes->getScopeEntityByIdentifier('faq.read'));

        $this->assertTrue($this->scopes->enable('faq.read', 'Read FAQs'));

        $this->assertInstanceOf(ScopeEntityInterface::class, $this->scopes->getScopeEntityByIdentifier('faq.read'));
        $this->assertSame(['faq.read' => 'Read FAQs'], $this->scopes->all());
    }

    public function testEnablingAScopeAgainChangesItsDescription(): void
    {
        $this->scopes->enable('faq.write', 'Write FAQs');
        $this->scopes->enable('faq.read', 'Read FAQs');

        $this->assertTrue($this->scopes->enable('faq.read', "Read FAQs, even 'drafts'"));

        $this->assertSame(
            ['faq.read' => "Read FAQs, even 'drafts'", 'faq.write' => 'Write FAQs'],
            $this->scopes->all(),
        );
    }

    public function testADisabledScopeCanNoLongerBeRequested(): void
    {
        $this->scopes->enable('faq.read', 'Read FAQs');

        $this->assertTrue($this->scopes->disable('faq.read'));

        $this->assertNull($this->scopes->getScopeEntityByIdentifier('faq.read'));
        $this->assertSame([], $this->scopes->all());
    }
}
