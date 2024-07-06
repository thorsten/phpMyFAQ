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
        $this->glossary->setLanguage('en');
    }

    protected function tearDown(): void
    {
        $this->glossary->delete(1);
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

    public function testInsertItemsIntoContent()
    {
        // Create a mock of the class containing the method you want to test
        $glossary = $this->getMockBuilder(Glossary::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAll'])
            ->getMock();

        // Define the return value for fetchAll() to simulate glossary items
        $glossaryItems = [
            ['item' => 'word', 'definition' => 'definition'],
            ['item' => 'phrase', 'definition' => 'definition'],
        ];
        $glossary->method('fetchAll')->willReturn($glossaryItems);

        // Define test cases with different input content
        $testCases = [
            // Case 1: No content
            [
                'input' => '',
                'expected' => '',
            ],

            // Case 2: Glossary item 'word' in the input content
            [
                'input' => 'This is a word.',
                'expected' => 'This is a <abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="definition" class="initialism">word</abbr>.',
            ],

            // Case 3: Glossary item 'phrase' in the input content
            [
                'input' => 'A phrase example.',
                'expected' => 'A <abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="definition" class="initialism">phrase</abbr> example.',
            ],

            // Case 4: Multiple glossary items in the input content
            [
                'input' => 'A phrase example with a word.',
                'expected' => 'A <abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="definition" class="initialism">phrase</abbr> example with a <abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="definition" class="initialism">word</abbr>.',
            ],
        ];

        // Iterate through each test case
        foreach ($testCases as $case) {
            // Call the method with the provided input content
            $output = $glossary->insertItemsIntoContent($case['input']);

            // Assert that the output matches the expected result
            $this->assertEquals($case['expected'], $output);
        }
    }
}
