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
    private string $databasePath;
    private Glossary $glossary;
    private array $createdIds = [];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-glossary-test-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->glossary = new Glossary($this->configuration);
        $this->glossary->setLanguage('en');
    }

    protected function tearDown(): void
    {
        foreach ($this->createdIds as $id) {
            $this->glossary->delete($id);
        }

        @unlink($this->databasePath);
    }

    public function testCreate(): void
    {
        $result = $this->glossary->create('testItem', 'testDefinition');

        $this->assertTrue($result);

        $result = $this->glossary->fetch($this->rememberCreatedId('testItem'));

        $this->assertEquals('testItem', $result['item']);
    }

    public function testUpdate(): void
    {
        $this->glossary->create('testItem', 'testDefinition');
        $id = $this->rememberCreatedId('testItem');

        $result = $this->glossary->update($id, 'testItem2', 'testDefinition2');

        $this->assertTrue($result);

        $result = $this->glossary->fetch($id);

        $this->assertEquals('testItem2', $result['item']);
    }

    public function testDelete(): void
    {
        $this->glossary->create('testItem', 'testDefinition');
        $id = $this->rememberCreatedId('testItem');

        $result = $this->glossary->delete($id);

        $this->assertTrue($result);

        $result = $this->glossary->fetch($id);

        $this->assertEmpty($result);
    }

    public function testFetchAll(): void
    {
        $this->glossary->create('testItem', 'testDefinition');

        $result = $this->glossary->fetchAll();

        $this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }

    private function rememberCreatedId(string $item): int
    {
        foreach ($this->glossary->fetchAll() as $entry) {
            if (($entry['item'] ?? null) !== $item) {
                continue;
            }

            $id = (int) $entry['id'];
            $this->createdIds[] = $id;

            return $id;
        }

        self::fail('Could not find created glossary item: ' . $item);
    }

    public function testInsertItemsIntoContent(): void
    {
        $glossary = $this->createPartialMock(Glossary::class, ['fetchAll']);

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
        $id = $this->rememberCreatedId('cItem');
        $firstFetch = $this->glossary->fetchAll();
        $this->assertNotEmpty($firstFetch);
        $this->glossary->update($id, 'cItemUpdated', 'cDefUpdated');
        $secondFetch = $this->glossary->fetchAll();
        $updatedItems = array_column($secondFetch, 'item');
        $this->assertContains('cItemUpdated', $updatedItems);
        $this->glossary->delete($id);
        $thirdFetch = $this->glossary->fetchAll();
        $this->assertNotContains('cItemUpdated', array_column($thirdFetch, 'item'));
    }

    public function testRepositoryErrorHandling(): void
    {
        $repoMock = $this->createPartialMock(GlossaryRepository::class, [
            'create',
            'update',
            'delete',
            'fetchAll',
            'fetch',
        ]);
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
