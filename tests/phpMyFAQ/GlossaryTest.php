<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class GlossaryTest extends TestCase
{
    private Configuration $config;

    private Glossary $glossary;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->config = new Configuration($dbHandle);
        $language = new Language($this->config);
        $language->setLanguage(false, 'en');
        $this->config->setLanguage($language);

        $this->glossary = new Glossary($this->config);
    }

    public function testCreate(): void
    {
        $result = $this->glossary->create('testItem', 'testDefinition');

        $this->assertTrue($result);

        $result = $this->glossary->fetch(1);

        $this->assertEquals('testItem', $result['item']);
    }

    public function testUpdate(): void
    {
        $this->glossary->create('testItem', 'testDefinition');

        $result = $this->glossary->update(1, 'testItem2', 'testDefinition2');

        $this->assertTrue($result);

        $result = $this->glossary->fetch(1);

        $this->assertEquals('testItem2', $result['item']);
    }

    public function testDelete(): void
    {
        $this->glossary->create('testItem', 'testDefinition');

        $result = $this->glossary->delete(1);

        $this->assertTrue($result);

        $result = $this->glossary->fetch(1);

        $this->assertEmpty($result);
    }

    public function testFetchAll(): void
    {
        $this->glossary->create('testItem', 'testDefinition');

        $result = $this->glossary->fetchAll();

        $this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }
}
