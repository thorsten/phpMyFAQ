<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[CoversClass(Relation::class)]
#[UsesNamespace('phpMyFAQ')]
final class RelationCategoryIdsTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private Relation $relation;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->relation = new Relation($this->configuration, $this->createStub(Category::class));

        foreach ([[10, 'en', 501], [10, 'en', 502], [11, 'en', 503], [12, 'de', 504]] as [$faqId, $lang, $categoryId]) {
            $this->configuration->getDb()->query(sprintf(
                "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang)"
                . " VALUES (%d, '%s', %d, '%s')",
                Database::getTablePrefix(),
                $categoryId,
                $lang,
                $faqId,
                $lang,
            ));
        }
    }

    public function testCategoryIdsAreGroupedPerFaqForTheRequestedLanguage(): void
    {
        $this->assertSame(
            [10 => [501, 502], 11 => [503]],
            $this->relation->getCategoryIdsForRecords([10, 11, 12], 'en'),
        );
        $this->assertSame([12 => [504]], $this->relation->getCategoryIdsForRecords([10, 11, 12], 'de'));
    }

    public function testInvalidAndDuplicateFaqIdsAreIgnored(): void
    {
        $this->assertSame([11 => [503]], $this->relation->getCategoryIdsForRecords([0, -1, 11, 11], 'en'));
        $this->assertSame([], $this->relation->getCategoryIdsForRecords([0, -5], 'en'));
        $this->assertSame([], $this->relation->getCategoryIdsForRecords([], 'en'));
    }

    public function testUnknownFaqsYieldNoEntry(): void
    {
        $this->assertSame([], $this->relation->getCategoryIdsForRecords([999], 'en'));
    }
}
