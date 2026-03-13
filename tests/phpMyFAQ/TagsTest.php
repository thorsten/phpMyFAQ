<?php

namespace phpMyFAQ;

use phpMyFAQ\Database;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Entity\Tag;
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

        $this->tags = new Tags($configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $testData = ['Foo', 'Bar', 'Baz'];
        $result = $this->tags->create(1, $testData);

        $this->assertTrue($result);
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
            '<a class="btn btn-outline-primary" title="Foo" href="http://example.com/./search.html?tagging_id=1">Foo</a>',
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

        $testData = ['Foo', 'Bar', 'Baz'];
        $this->tags->create(1, $testData);

        $this->assertCount(1, $this->tags->getFaqsByTagId(1));
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

    private function seedFaqRecord(int $id, string $lang = 'en', string $active = 'yes'): void
    {
        $query = sprintf(
            "INSERT OR REPLACE INTO faqdata (
                id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email,
                comment, updated, date_start, date_end, created, notes, sticky_order
            ) VALUES (
                %d, '%s', %d, 0, '%s', 0, '', 'Test question %d', 'Test answer %d', 'Tester', 'test@example.com',
                'y', '20260101000000', '00000000000000', '99991231235959', CURRENT_TIMESTAMP, '', NULL
            )",
            $id,
            $lang,
            $id,
            $active,
            $id,
            $id,
        );

        $this->dbHandle->query($query);
    }
}
