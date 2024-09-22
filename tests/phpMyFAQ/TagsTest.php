<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Tag;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;

class TagsTest extends TestCase
{
    private Tags $tags;

    /**
     * @throws PluginException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.referenceURL', 'http://example.com');

        $language = new Language($configuration);
        $language->setLanguage(false, 'en');
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
            '<a class="btn btn-outline-primary" title="Foo" href="http://example.com/index.php?action=search&amp;tagging_id=1">Foo</a>',
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
}
