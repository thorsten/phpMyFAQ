<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Tag;
use PHPUnit\Framework\TestCase;

class TagsTest extends TestCase
{
    private Tags $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->tags = new Tags($configuration);
    }

    protected function tearDown(): void
    {
        $this->tags->deleteByRecordId(1);
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
}
