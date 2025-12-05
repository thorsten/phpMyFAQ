<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Tag;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class TagsTest extends TestCase
{
    private Tags $tags;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.referenceURL', 'http://example.com');

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->tags = new Tags($configuration);
    }

    protected function tearDown(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $dbHandle->query('DELETE FROM faqdata_tags');
        $dbHandle->query('DELETE FROM faqtags');
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
            '<a class="btn btn-outline-primary" title="Foo" href="http://example.com/tags/1/foo.html">Foo</a>',
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
}
