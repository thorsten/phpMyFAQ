<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Glossary\GlossaryRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class GlossaryTest extends TestCase
{
    private Configuration $configuration;

    private Glossary $glossary;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->glossary = new Glossary($this->configuration);
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

    public function testInsertItemsIntoContent(): void
    {
        $glossary = $this
            ->getMockBuilder(Glossary::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAll'])
            ->getMock();

        $glossaryItems = [
            ['item' => 'word', 'definition' => 'definition'],
            ['item' => 'phrase', 'definition' => 'definition'],
        ];
        $glossary->method('fetchAll')->willReturn($glossaryItems);

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

    public function testCacheInvalidationOnCreateUpdateDelete(): void
    {
        $this->glossary->create('cItem', 'cDef');
        $firstFetch = $this->glossary->fetchAll();
        $this->assertNotEmpty($firstFetch);
        $this->glossary->update($firstFetch[0]['id'], 'cItemUpdated', 'cDefUpdated');
        $secondFetch = $this->glossary->fetchAll();
        $this->assertNotSame($firstFetch[0]['item'], $secondFetch[0]['item']);
        $this->glossary->delete($secondFetch[0]['id']);
        $thirdFetch = $this->glossary->fetchAll();
        $this->assertEmpty($thirdFetch);
    }

    public function testRepositoryErrorHandling(): void
    {
        $repoMock = $this
            ->getMockBuilder(GlossaryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create', 'update', 'delete', 'fetchAll', 'fetch'])
            ->getMock();
        $repoMock->method('fetchAll')->willReturn([]);
        $repoMock->method('fetch')->willReturn([]);
        $repoMock->method('create')->willReturn(false);
        $repoMock->method('update')->willReturn(false);
        $repoMock->method('delete')->willReturn(false);

        $glossary = new Glossary($this->configuration, $repoMock);
        $glossary->setLanguage('en');
        $this->assertFalse($glossary->create('x', 'y'));
        $this->assertFalse($glossary->update(1, 'x', 'y'));
        $this->assertFalse($glossary->delete(1));
        $this->assertEmpty($glossary->fetchAll());
        $this->assertEmpty($glossary->fetch(1));
    }
}
