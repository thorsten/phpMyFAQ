<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SqlOperationTest extends TestCase
{
    private MockObject&Configuration $configuration;
    private MockObject&DatabaseDriver $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->database);
    }

    public function testGetType(): void
    {
        $operation = new SqlOperation($this->configuration, 'SELECT 1');

        $this->assertEquals('sql', $operation->getType());
    }

    public function testGetQuery(): void
    {
        $query = 'SELECT * FROM users';
        $operation = new SqlOperation($this->configuration, $query);

        $this->assertEquals($query, $operation->getQuery());
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $operation = new SqlOperation($this->configuration, 'SELECT 1', 'Custom description');

        $this->assertEquals('Custom description', $operation->getDescription());
    }

    public function testGetDescriptionForCreateTable(): void
    {
        $operation = new SqlOperation($this->configuration, 'CREATE TABLE faqtest (id INT)');

        $this->assertEquals('Create table faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForCreateTableIfNotExists(): void
    {
        $operation = new SqlOperation($this->configuration, 'CREATE TABLE IF NOT EXISTS faqtest (id INT)');

        $this->assertEquals('Create table faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForAlterTable(): void
    {
        $operation = new SqlOperation($this->configuration, 'ALTER TABLE faqtest ADD COLUMN name VARCHAR(255)');

        $this->assertEquals('Alter table faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForDropTable(): void
    {
        $operation = new SqlOperation($this->configuration, 'DROP TABLE faqtest');

        $this->assertEquals('Drop table faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForCreateIndex(): void
    {
        $operation = new SqlOperation($this->configuration, 'CREATE INDEX idx_test ON faqtest (name)');

        $this->assertEquals('Create index idx_test', $operation->getDescription());
    }

    public function testGetDescriptionForInsert(): void
    {
        $operation = new SqlOperation($this->configuration, 'INSERT INTO faqtest (id, name) VALUES (1, "test")');

        $this->assertEquals('Insert into faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForUpdate(): void
    {
        $operation = new SqlOperation($this->configuration, 'UPDATE faqtest SET name = "new" WHERE id = 1');

        $this->assertEquals('Update faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForDelete(): void
    {
        $operation = new SqlOperation($this->configuration, 'DELETE FROM faqtest WHERE id = 1');

        $this->assertEquals('Delete from faqtest', $operation->getDescription());
    }

    public function testGetDescriptionForUnknownQuery(): void
    {
        $operation = new SqlOperation($this->configuration, 'VACUUM');

        $this->assertEquals('Execute SQL query', $operation->getDescription());
    }

    public function testExecuteSuccess(): void
    {
        $this->database
            ->expects($this->once())
            ->method('query')
            ->with('SELECT 1')
            ->willReturn(true);

        $operation = new SqlOperation($this->configuration, 'SELECT 1');
        $result = $operation->execute();

        $this->assertTrue($result);
    }

    public function testExecuteFailure(): void
    {
        $this->database
            ->expects($this->once())
            ->method('query')
            ->willThrowException(new \phpMyFAQ\Core\Exception('Query failed'));

        $operation = new SqlOperation($this->configuration, 'INVALID SQL');
        $result = $operation->execute();

        $this->assertFalse($result);
    }

    public function testToArray(): void
    {
        $query = 'SELECT * FROM faqtest';
        $operation = new SqlOperation($this->configuration, $query, 'Test query');

        $expected = [
            'type' => 'sql',
            'description' => 'Test query',
            'query' => $query,
        ];

        $this->assertEquals($expected, $operation->toArray());
    }
}
