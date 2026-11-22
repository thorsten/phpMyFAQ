<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Category 700 holds three published FAQs that everyone may read: 1 is open, 2 starts
 * tomorrow, 3 ended yesterday. Readers must only ever count the open one.
 */
#[CoversClass(Relation::class)]
#[UsesNamespace('phpMyFAQ')]
final class RelationPublicationWindowTest extends TestCase
{
    use TestDatabaseTrait;

    private const int CATEGORY_ID = 700;

    private Configuration $configuration;

    private Relation $relation;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->configuration->set('security.permLevel', 'basic');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $category = $this->createStub(Category::class);
        $category->method('getUser')->willReturn(-1);
        $category->method('getGroups')->willReturn([-1]);

        $this->relation = new Relation($this->configuration, $category);

        $db = $this->configuration->getDb();
        $db->query(sprintf(
            'INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active)'
            . " VALUES (%d, 'en', 0, 'Scheduling', '', 1, -1, 1)",
            self::CATEGORY_ID,
        ));

        $windows = [
            1 => ['00000000000000', '99991231235959'],
            2 => [date('YmdHis', strtotime('+1 day')), '99991231235959'],
            3 => ['00000000000000', date('YmdHis', strtotime('-1 day'))],
        ];

        foreach ($windows as $faqId => [$dateStart, $dateEnd]) {
            $db->query(sprintf(
                'INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content,'
                . ' author, email, comment, updated, date_start, date_end, created, notes, sticky_order)'
                . " VALUES (%d, 'en', %d, 0, 'published', 0, '', 'Question', 'Answer', 'Author',"
                . " 'author@example.com', 'y', '20260301010101', '%s', '%s', '2026-03-01 01:01:01', '', 0)",
                $faqId,
                1000 + $faqId,
                $dateStart,
                $dateEnd,
            ));
            $db->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $faqId));
            $db->query(sprintf(
                'INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)'
                . " VALUES (%d, 'en', %d, 'en')",
                self::CATEGORY_ID,
                $faqId,
            ));
        }
    }

    public function testThePublicCategoryListCountsOnlyFaqsInsideThePublicationWindow(): void
    {
        $categories = $this->relation->getCategoryWithFaqs();

        $this->assertSame(1, $categories[self::CATEGORY_ID]['faqs']);
    }

    public function testActiveFaqsPerCategoryCountOnlyFaqsInsideThePublicationWindow(): void
    {
        $this->assertSame([self::CATEGORY_ID => 1], $this->relation->getNumberOfFaqsPerCategory(onlyActive: true));
    }

    public function testActiveFaqsPerRestrictedCategoryCountOnlyFaqsInsideThePublicationWindow(): void
    {
        foreach ([1, 2, 3] as $faqId) {
            $this->configuration
                ->getDb()
                ->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', $faqId));
        }

        $this->assertSame(
            [self::CATEGORY_ID => 1],
            $this->relation->getNumberOfFaqsPerCategory(categoryRestriction: true, onlyActive: true),
        );
    }

    public function testTheAdministrationCountsScheduledAndExpiredFaqsToo(): void
    {
        $this->assertSame([self::CATEGORY_ID => 3], $this->relation->getNumberOfFaqsPerCategory());
    }
}
