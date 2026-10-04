<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * Runs the admin FAQ queries against a private copy of the SQLite test database.
 */
#[CoversClass(Faq::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqDatabaseTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private Faq $faq;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->faq = new Faq($this->configuration);
    }

    private function insertFaq(int $id, FaqStatus $status, string $updated, ?int $categoryId = null): void
    {
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            "INSERT INTO %sfaqdata (id, lang, solution_id, status, sticky, thema, content, author, email, updated)"
            . " VALUES (%d, 'en', %d, '%s', 0, 'Question %d', 'Answer', 'Tester', 'tester@example.org', '%s')",
            Database::getTablePrefix(),
            $id,
            1000 + $id,
            $status->value,
            $id,
            $updated,
        ));

        if ($categoryId === null) {
            return;
        }

        $db->query(sprintf(
            "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang)"
            . " VALUES (%d, 'en', %d, 'en')",
            Database::getTablePrefix(),
            $categoryId,
            $id,
        ));
    }

    /**
     * @return array{orphaned: int, stale: int}
     */
    private function statistics(): array
    {
        return $this->faq->getContentHealthStatistics();
    }

    public function testContentHealthCountsOrphanedAndStalePublishedFaqs(): void
    {
        $before = $this->statistics();
        $recent = date('YmdHis');
        $stale = date('YmdHis', (int) strtotime('-2 years'));

        // Published, in a category, recently updated: healthy.
        $this->insertFaq(90001, FaqStatus::Published, $recent, 1);
        // Published, no category: orphaned.
        $this->insertFaq(90002, FaqStatus::Published, $recent);
        // Published, in a category, not touched for two years: stale.
        $this->insertFaq(90003, FaqStatus::Published, $stale, 1);
        // Draft, orphaned and stale: not counted at all.
        $this->insertFaq(90004, FaqStatus::Draft, $stale);

        $after = $this->statistics();

        $this->assertSame($before['orphaned'] + 1, $after['orphaned']);
        $this->assertSame($before['stale'] + 1, $after['stale']);
    }

    public function testContentHealthHonoursTheStaleThreshold(): void
    {
        $this->insertFaq(90005, FaqStatus::Published, date('YmdHis', (int) strtotime('-40 days')), 1);

        $this->assertSame(
            $this->faq->getContentHealthStatistics(30)['stale'],
            $this->faq->getContentHealthStatistics(60)['stale'] + 1,
        );
    }

    public function testCategoryListingCanBeLimitedToFaqsCreatedWithinTheLastMonth(): void
    {
        $this->faq->setLanguage('en');
        $db = $this->configuration->getDb();
        $this->insertFaq(90020, FaqStatus::Published, date('YmdHis'), 77);
        $this->insertFaq(90021, FaqStatus::Published, date('YmdHis'), 77);
        $db->query(sprintf(
            "UPDATE %sfaqdata SET created = '%s' WHERE id = 90021",
            Database::getTablePrefix(),
            date('Y-m-d H:i:s', (int) strtotime('-2 months')),
        ));

        $all = array_column($this->faq->getAllFaqsByCategory(77), 'id');
        $new = array_column($this->faq->getAllFaqsByCategory(77, null, true), 'id');

        $this->assertSame([90020, 90021], $all);
        $this->assertSame([90020], $new);
    }

    public function testStickyOrderHonoursGroupPermissionsAboveBasicLevel(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $db = $this->configuration->getDb();
        $this->insertFaq(90010, FaqStatus::Published, date('YmdHis'), 1);
        $this->insertFaq(90011, FaqStatus::Published, date('YmdHis'), 1);
        $db->query(sprintf(
            'INSERT INTO %sfaqdata_group (record_id, group_id) VALUES (90010, 7)',
            Database::getTablePrefix(),
        ));

        // A member of group 7 may reorder the restricted FAQ and the unrestricted one.
        $this->assertTrue($this->faq->setStickyFaqOrder(['90011', 90010, 90010, 0], 5, [7]));
        $orders = $db->fetchAll($db->query(sprintf(
            'SELECT id, sticky_order FROM %sfaqdata WHERE id IN (90010, 90011) ORDER BY id',
            Database::getTablePrefix(),
        )));
        $this->assertSame([[90010, 2], [90011, 1]], array_map(
            static fn(object $row): array => [(int) $row->id, (int) $row->sticky_order],
            $orders ?? [],
        ));

        // Without a matching group the restricted FAQ blocks the whole reorder.
        $this->assertFalse($this->faq->setStickyFaqOrder([90010], 5, [8]));
        $this->assertFalse($this->faq->setStickyFaqOrder([90010], 5, []));
    }

    public function testStickyOrderAllowsAnExplicitlyGrantedUserAboveBasicLevel(): void
    {
        $this->configuration->set('security.permLevel', 'large');
        $db = $this->configuration->getDb();
        $this->insertFaq(90012, FaqStatus::Published, date('YmdHis'), 1);
        $db->query(sprintf(
            'INSERT INTO %sfaqdata_user (record_id, user_id) VALUES (90012, 5)',
            Database::getTablePrefix(),
        ));

        $this->assertTrue($this->faq->setStickyFaqOrder([90012], 5, []));
        $this->assertFalse($this->faq->setStickyFaqOrder([90012], 6, []));
    }
}
