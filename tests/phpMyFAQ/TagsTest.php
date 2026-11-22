<?php

namespace phpMyFAQ;

use phpMyFAQ\Database;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Entity\Tag;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class TagsTest extends TestCase
{
    private Tags $tags;
    private PdoSqlite $dbHandle;
    private string $databaseFile;
    private Configuration $configuration;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-tags-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->databaseFile, '', '');
        $configuration = new Configuration($this->dbHandle);
        $configuration->set('main.referenceURL', 'http://example.com');
        $configuration->set('security.permLevel', 'basic');

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite');
        Database::setTablePrefix('');

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->configuration = $configuration;
        $this->tags = new Tags($configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        @unlink($this->databaseFile);
        $_SERVER['HTTP_HOST'] = 'localhost';

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $testData = ['Foo', 'Bar', 'Baz'];
        $result = $this->tags->create(1, $testData);

        $this->assertTrue($result);
    }

    /**
     * Saving the tags of a FAQ again must reuse the stored tags, whether the FAQ is published
     * or readable by the current user or not: the tag is looked up in the tag table itself.
     */
    public function testCreateReusesTheTagsOfADraftFaq(): void
    {
        // Record 4711 has no faqdata row at all, like a FAQ that is not published.
        $this->tags->create(4711, ['Foo', 'Bar']);
        $this->tags->create(4711, ['foo', 'Bar', 'FOO']);

        $this->assertSame(2, $this->countRows('faqtags'));
        $this->assertSame(2, $this->countRows('faqdata_tags'));
        $this->assertSame(['Bar', 'Foo'], array_values($this->tags->getAllTagsById(4711)));
    }

    public function testCreateReusesTagsBeyondTheTagCloudSize(): void
    {
        $names = [];
        for ($i = 1; $i <= (PMF_TAGS_CLOUD_RESULT_SET_SIZE + 5); ++$i) {
            $names[] = sprintf('Tag %03d', $i);
        }

        $this->seedFaqRecord(1);
        $this->dbHandle->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (1, -1)');
        $this->tags->create(1, $names);
        $this->tags->create(1, $names);

        $this->assertSame(count($names), $this->countRows('faqtags'));
    }

    public function testGetAllTagsSearchesCaseInsensitivelyOnBothPostgresDrivers(): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $previousType = $dbTypeProperty->getValue();

        $queries = [];
        $database = $this->createMock(Database\DatabaseDriver::class);
        $database->method('escape')->willReturnArgument(0);
        $database
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): bool {
                $queries[] = $query;

                return false;
            });
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);
        $configuration->method('get')->willReturn('basic');

        try {
            foreach (['pgsql', 'pdo_pgsql', 'pdo_mysql'] as $type) {
                $dbTypeProperty->setValue(null, $type);
                new Tags($configuration)->getAllTags('Fo');
            }
        } finally {
            $dbTypeProperty->setValue(null, $previousType);
        }

        $this->assertStringContainsString("tagging_name ILIKE 'Fo%'", $queries[0]);
        $this->assertStringContainsString("tagging_name ILIKE 'Fo%'", $queries[1]);
        $this->assertStringContainsString("tagging_name LIKE 'Fo%'", $queries[2]);
    }

    private function countRows(string $table): int
    {
        $result = $this->dbHandle->query(sprintf('SELECT COUNT(*) AS amount FROM %s', $table));

        return (int) $this->dbHandle->fetchObject($result)->amount;
    }

    public function testDeleteByRecordId(): void
    {
        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $this->assertTrue($this->tags->deleteByRecordId(1));
    }

    public function testUpdate(): void
    {
        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $tag = new Tag();
        $tag->setId(1);
        $tag->setName('Foooooooo');

        $this->assertTrue($this->tags->update($tag));
        $this->assertEquals('Foooooooo', $this->tags->getTagNameById(1));
    }

    public function testDelete(): void
    {
        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $this->assertTrue($this->tags->delete(1));
        $this->assertEmpty($this->tags->getTagNameById(1));
    }

    public function testGetAllLinkTagsById(): void
    {
        $testData = ['Foo'];
        $this->tags->create(1, $testData);

        $this->assertEquals(
            '<a class="btn btn-outline-primary" title="Foo" href="http://example.com/search.html?tagging_id=1">Foo</a>',
            $this->tags->getAllLinkTagsById(1),
        );
    }

    public function testGetAllTagsById(): void
    {
        $this->assertEmpty($this->tags->getAllTagsById(1));

        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $this->assertCount(3, $this->tags->getAllTagsById(1));
    }

    public function testGetFaqsByTagId(): void
    {
        $this->assertEmpty($this->tags->getFaqsByTagId(1));

        // A tag only lists FAQs a reader can open, so the tagged record has to exist and be published.
        $this->seedFaqRecord(1);
        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $this->assertSame([1], $this->tags->getFaqsByTagId(1));
    }

    public function testGetFaqsByIntersectionTags(): void
    {
        $this->seedFaqRecord(1);
        $this->seedFaqRecord(2);

        $this->assertEmpty($this->tags->getFaqsByIntersectionTags(['Unknown']));

        $this->tags->create(1, ['Shared', 'OnlyOne']);
        $this->tags->create(2, ['Shared', 'Other']);

        $intersection = $this->tags->getFaqsByIntersectionTags(['Shared', 'OnlyOne']);

        $this->assertSame([1], $intersection);
    }

    public function testSetUserAndGroups(): void
    {
        $this->tags->setUser(42);
        $this->tags->setGroups([1, 2, 3]);

        // Test that methods can be called - actual permission filtering
        // requires database setup with faqdata_user and faqdata_group tables
        $this->assertIsArray($this->tags->getPopularTags());
        $this->assertIsArray($this->tags->getAllTags());
    }

    public function testGetAllTagsSearchTreatsWildcardsLiterally(): void
    {
        // getAllTags() only lists tags of visible FAQs, so seed a published record first
        $this->dbHandle->query(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES (1, 'en', 1000, 0, 'published', 0, '', 'Question', 'Answer', 'Author', 'author@example.com', 'y', '20260301010101', '00000000000000', '99991231235959', '2026-03-01 01:01:01', '', 0)",
        );
        $this->dbHandle->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (1, -1)');
        $this->tags->setUser(-1);
        $this->tags->setGroups([-1]);
        $this->tags->create(1, ['Alpha', 'Beta', 'Under_score']);

        $this->assertSame([], array_values($this->tags->getAllTags('%')));
        $this->assertSame([], array_values($this->tags->getAllTags('_lpha')));
        $this->assertSame(['Under_score'], array_values($this->tags->getAllTags('Under_')));
        $this->assertSame(['Alpha'], array_values($this->tags->getAllTags('Al')));
    }

    public function testGetPopularTagsWithPermissions(): void
    {
        // Set up tags for a record
        $testData = ['SecretTag'];
        $this->tags->create(1, $testData);

        // Set user and groups
        $this->tags->setUser(1);
        $this->tags->setGroups([1]);

        // Should return tags (with proper database setup)
        $popularTags = $this->tags->getPopularTags();
        $this->assertIsArray($popularTags);
    }

    public function testGetPopularTagsAsArray(): void
    {
        $this->seedFaqRecord(1);
        $this->seedFaqRecord(2);
        $this->dbHandle->query('INSERT OR REPLACE INTO faqdata_user (record_id, user_id) VALUES (1, -1)');
        $this->dbHandle->query('INSERT OR REPLACE INTO faqdata_user (record_id, user_id) VALUES (2, -1)');

        $this->tags->create(1, ['Foo', 'Bar']);
        $this->tags->create(2, ['Foo']);

        $popularTags = $this->tags->getPopularTagsAsArray(2);

        $this->assertNotEmpty($popularTags);
        $this->assertSame('Foo', $popularTags[0]['tagName']);
        $this->assertSame(2, $popularTags[0]['tagFrequency']);
        $this->assertArrayHasKey('tagId', $popularTags[0]);
    }

    public function testGetAllTagsWithPermissions(): void
    {
        // Set up tags for a record
        $testData = ['VisibleTag'];
        $this->tags->create(1, $testData);

        // Set user and groups
        $this->tags->setUser(-1);
        $this->tags->setGroups([-1]);

        // Should return tags (with proper database setup)
        $allTags = $this->tags->getAllTags();
        $this->assertIsArray($allTags);
    }

    public function testGetAllTagsStopsAtTheRequestedLimit(): void
    {
        $this->seedFaqRecord(1);
        $this->dbHandle->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (1, -1)');
        $this->tags->setUser(-1);
        $this->tags->setGroups([-1]);
        $this->tags->create(1, ['Alpha', 'Beta', 'Gamma']);

        $allTags = $this->tags->getAllTags(null, 2);

        $this->assertCount(2, $allTags);
        $this->assertSame(['Alpha', 'Beta'], array_values($allTags));
    }

    public function testBypassingThePermissionCheckSkipsThePermissionClause(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $tags = new Tags($this->configuration);
        $tags->setUser(-1);
        $tags->setGroups([-1]);

        $this->assertSame($tags, $tags->setBypassPermissionCheck());
        $this->assertIsArray($tags->getAllTags());
        $this->assertStringNotContainsString('fdg.group_id IN', $this->dbHandle->log());
    }

    public function testPermissionChecksNormalizeGroupIds(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $this->tags = new Tags($this->configuration);

        $this->tags->setUser(-1);
        $this->tags->setGroups(['-1) OR 1=1 -- ', '2']);

        $this->assertIsArray($this->tags->getPopularTags());
        $this->assertStringContainsString('fdg.group_id IN (-1, 2)', $this->dbHandle->log());
        $this->assertStringNotContainsString('OR 1=1', $this->dbHandle->log());
    }

    public function testReadersGetNoTagsOfFaqsOutsideThePublicationWindow(): void
    {
        $this->seedTaggedFaqsAroundThePublicationWindow();
        $this->tags->setUser(-1);
        $this->tags->setGroups([-1]);

        $this->assertSame(['Open', 'Shared'], array_values($this->tags->getAllTags()));
    }

    public function testTheAdministrationKeepsTheTagsOfScheduledAndExpiredFaqs(): void
    {
        $this->seedTaggedFaqsAroundThePublicationWindow();
        $this->tags->setBypassPermissionCheck();

        $this->assertSame(['Expired', 'Open', 'Scheduled', 'Shared'], array_values($this->tags->getAllTags()));
    }

    public function testPopularTagsCountOnlyFaqsInsideThePublicationWindow(): void
    {
        $this->seedTaggedFaqsAroundThePublicationWindow();
        $this->tags->setUser(-1);
        $this->tags->setGroups([-1]);

        $popularTags = $this->tags->getPopularTagsAsArray();

        $frequencies = array_column($popularTags, 'tagFrequency', 'tagName');
        ksort($frequencies);
        $this->assertSame(['Open' => 1, 'Shared' => 1], $frequencies);
    }

    public function testFaqsByTagIdSkipsFaqsOutsideThePublicationWindow(): void
    {
        $this->seedTaggedFaqsAroundThePublicationWindow();
        $sharedTagId = (int) array_search('Shared', $this->tags->getAllTagsById(1), strict: true);

        $this->assertSame([1], $this->tags->getFaqsByTagId($sharedTagId));
    }

    public function testFaqsByTagIdSkipsDrafts(): void
    {
        $this->seedFaqRecord(1, active: 'no');
        $this->tags->create(1, ['Draft']);
        $draftTagId = (int) array_key_first($this->tags->getAllTagsById(1));

        $this->assertSame([], $this->tags->getFaqsByTagId($draftTagId));
    }

    public function testFaqsByIntersectionTagsSkipsFaqsOutsideThePublicationWindow(): void
    {
        $this->seedTaggedFaqsAroundThePublicationWindow();

        $this->assertSame([1], $this->tags->getFaqsByIntersectionTags(['Shared']));
        $this->assertSame([], $this->tags->getFaqsByIntersectionTags(['Scheduled']));
        $this->assertSame([], $this->tags->getFaqsByIntersectionTags(['Expired']));
    }

    /**
     * FAQ 1 is open, FAQ 2 starts tomorrow, FAQ 3 ended yesterday; all three are published,
     * readable by everyone and carry the tag "Shared".
     */
    private function seedTaggedFaqsAroundThePublicationWindow(): void
    {
        $tomorrow = date('YmdHis', strtotime('+1 day'));
        $yesterday = date('YmdHis', strtotime('-1 day'));

        $this->seedFaqRecord(1);
        $this->seedFaqRecord(2);
        $this->seedFaqRecord(3);
        $this->dbHandle->query(sprintf("UPDATE faqdata SET date_start = '%s' WHERE id = 2", $tomorrow));
        $this->dbHandle->query(sprintf("UPDATE faqdata SET date_end = '%s' WHERE id = 3", $yesterday));

        foreach ([1, 2, 3] as $faqId) {
            $this->dbHandle->query(sprintf(
                'INSERT OR REPLACE INTO faqdata_user (record_id, user_id) VALUES (%d, -1)',
                $faqId,
            ));
        }

        $this->tags->create(1, ['Shared', 'Open']);
        $this->tags->create(2, ['Shared', 'Scheduled']);
        $this->tags->create(3, ['Shared', 'Expired']);
    }

    private function seedFaqRecord(int $id, string $lang = 'en', string $active = 'yes'): void
    {
        $status = 'yes' === $active ? FaqStatus::Published->value : FaqStatus::Draft->value;

        $query = sprintf("INSERT OR REPLACE INTO faqdata (
                id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email,
                comment, updated, date_start, date_end, created, notes, sticky_order
            ) VALUES (
                %d, '%s', %d, 0, '%s', 0, '', 'Test question %d', 'Test answer %d', 'Tester', 'test@example.com',
                'y', '20260101000000', '00000000000000', '99991231235959', CURRENT_TIMESTAMP, '', NULL
            )", $id, $lang, $id, $status, $id, $id);

        $this->dbHandle->query($query);
    }
}
