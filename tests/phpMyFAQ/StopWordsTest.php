<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class StopWordsTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private StopWords $stopWords;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->stopWords = new StopWords($configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->query(sprintf("DELETE FROM %s WHERE lang = '%s'", $this->stopWords->getTableName(), 'test'));
    }

    public function testSetLanguage(): void
    {
        $this->stopWords->setLanguage('test');
        $this->assertEquals('test', $this->stopWords->getLanguage());
    }

    public function testAdd(): void
    {
        $this->stopWords->setLanguage('test');
        $this->assertTrue($this->stopWords->add('test'));
        $this->assertFalse($this->stopWords->add('test'));
    }

    public function testGetTableName(): void
    {
        $this->assertEquals('faqstopwords', $this->stopWords->getTableName());
    }

    public function testUpdate(): void
    {
        $this->stopWords->setLanguage('test');
        $this->assertTrue($this->stopWords->add('test'));
        $this->assertTrue($this->stopWords->update(1, 'test2'));
    }

    public function testRemove(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertTrue($this->stopWords->remove(1));
    }

    public function testMatch(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertTrue($this->stopWords->match('test'));
        $this->assertFalse($this->stopWords->match('test2'));
    }

    public function testMatchWithEmptyWord(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertFalse($this->stopWords->match(''));
    }

    public function testGetByLang(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertIsArray($this->stopWords->getByLang());
    }

    public function testClean(): void
    {
        $input = 'This is a test 42 string of Foobar test words';
        $result = $this->stopWords->clean($input);

        $this->assertIsArray($result);
        $this->assertEquals(['test', 'string', 'foobar', 'words'], $result);
    }

    public function testCheckBannedWord(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertTrue($this->stopWords->checkBannedWord('test'));
        $this->assertFalse($this->stopWords->checkBannedWord('abolon'));
    }

    public function testCheckBannedWordWithEmptyString(): void
    {
        $this->stopWords->setLanguage('test');
        $this->stopWords->add('test');
        $this->assertTrue($this->stopWords->checkBannedWord(''));
    }
}
