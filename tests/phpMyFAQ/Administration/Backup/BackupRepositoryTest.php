<?php

namespace phpMyFAQ\Administration\Backup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class BackupRepositoryTest
 *
 * @package phpMyFAQ\Administration\Backup
 */
#[AllowMockObjectsWithoutExpectations]
class BackupRepositoryTest extends TestCase
{
    private DatabaseDriver $mockDb;
    private BackupRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Configuration
        $mockConfiguration = $this->createStub(Configuration::class);

        // Mock DatabaseDriver
        $this->mockDb = $this->createMock(DatabaseDriver::class);

        // Setup Configuration to return mocked database
        $mockConfiguration->method('getDb')->willReturn($this->mockDb);

        // Create a BackupRepository instance
        $this->repository = new BackupRepository($mockConfiguration);
    }

    public function testGetNumberOfEntries(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(5);

        $result = $this->repository->getNumberOfEntries();

        $this->assertEquals(5, $result);
    }

    public function testGetNumberOfEntriesWhenEmpty(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->repository->getNumberOfEntries();

        $this->assertEquals(0, $result);
    }

    public function testGetAll(): void
    {
        $mockBackups = [
            (object) [
                'id' => 1,
                'filename' => 'backup1.sql',
                'authkey' => 'key1',
                'authcode' => 'code1',
                'created' => '2025-12-22 10:00:00'
            ],
            (object) [
                'id' => 2,
                'filename' => 'backup2.sql',
                'authkey' => 'key2',
                'authcode' => 'code2',
                'created' => '2025-12-22 11:00:00'
            ]
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn($mockBackups);

        $result = $this->repository->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals('backup1.sql', $result[0]->filename);
        $this->assertEquals('backup2.sql', $result[1]->filename);
    }

    public function testGetAllWhenEmpty(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn([]);

        $result = $this->repository->getAll();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllWhenQueryFails(): void
    {
        $this->mockDb->method('query')->willReturn(false);
        $this->mockDb->method('fetchAll')->willReturn(null);

        $result = $this->repository->getAll();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByFilename(): void
    {
        $mockBackup = (object) [
            'id' => 1,
            'filename' => 'test-backup.sql',
            'authkey' => 'test-key',
            'authcode' => 'test-code',
            'created' => '2025-12-22 10:00:00'
        ];

        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchObject')->willReturn($mockBackup);

        $result = $this->repository->findByFilename('test-backup.sql');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('test-backup.sql', $result->filename);
        $this->assertEquals('test-key', $result->authkey);
    }

    public function testFindByFilenameNotFound(): void
    {
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->repository->findByFilename('nonexistent.sql');

        $this->assertNull($result);
    }

    public function testFindByFilenameWithEmptyString(): void
    {
        $result = $this->repository->findByFilename('');

        $this->assertNull($result);
    }

    public function testFindByFilenameEscapesInput(): void
    {
        $this->mockDb->expects($this->once())
            ->method('escape')
            ->with("test'; DROP TABLE faqbackup; --")
            ->willReturn("test\\'; DROP TABLE faqbackup; --");

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $this->repository->findByFilename("test'; DROP TABLE faqbackup; --");
    }

    public function testAdd(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->repository->add(
            'backup.sql',
            'authkey123',
            'authcode456',
            '2025-12-22 10:00:00'
        );

        $this->assertTrue($result);
    }

    public function testAddWithEmptyFilename(): void
    {
        $result = $this->repository->add(
            '',
            'authkey123',
            'authcode456',
            '2025-12-22 10:00:00'
        );

        $this->assertFalse($result);
    }

    public function testAddWithEmptyAuthKey(): void
    {
        $result = $this->repository->add(
            'backup.sql',
            '',
            'authcode456',
            '2025-12-22 10:00:00'
        );

        $this->assertFalse($result);
    }

    public function testAddWithEmptyAuthCode(): void
    {
        $result = $this->repository->add(
            'backup.sql',
            'authkey123',
            '',
            '2025-12-22 10:00:00'
        );

        $this->assertFalse($result);
    }

    public function testAddWithEmptyCreated(): void
    {
        $result = $this->repository->add(
            'backup.sql',
            'authkey123',
            'authcode456',
            ''
        );

        $this->assertFalse($result);
    }

    public function testAddEscapesAllInputs(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->expects($this->exactly(4))
            ->method('escape')
            ->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $this->repository->add(
            'backup.sql',
            'authkey123',
            'authcode456',
            '2025-12-22 10:00:00'
        );
    }

    public function testDeleteById(): void
    {
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->repository->deleteById(5);

        $this->assertTrue($result);
    }

    public function testDeleteByIdWithZero(): void
    {
        $result = $this->repository->deleteById(0);

        $this->assertFalse($result);
    }

    public function testDeleteByIdWithNegative(): void
    {
        $result = $this->repository->deleteById(-1);

        $this->assertFalse($result);
    }

    public function testDeleteByIdWhenQueryFails(): void
    {
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->repository->deleteById(5);

        $this->assertFalse($result);
    }

    public function testDeleteByFilename(): void
    {
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->repository->deleteByFilename('backup.sql');

        $this->assertTrue($result);
    }

    public function testDeleteByFilenameWithEmptyString(): void
    {
        $result = $this->repository->deleteByFilename('');

        $this->assertFalse($result);
    }

    public function testDeleteByFilenameEscapesInput(): void
    {
        $this->mockDb->expects($this->once())
            ->method('escape')
            ->with("backup'; DROP TABLE faqbackup; --")
            ->willReturn("backup\\'; DROP TABLE faqbackup; --");

        $this->mockDb->method('query')->willReturn(true);

        $this->repository->deleteByFilename("backup'; DROP TABLE faqbackup; --");
    }

    public function testDeleteByFilenameWhenQueryFails(): void
    {
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->repository->deleteByFilename('backup.sql');

        $this->assertFalse($result);
    }

    public function testDeleteAll(): void
    {
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->repository->deleteAll();

        $this->assertTrue($result);
    }

    public function testDeleteAllWhenQueryFails(): void
    {
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->repository->deleteAll();

        $this->assertFalse($result);
    }

    public function testGetAllOrdersByIdDesc(): void
    {
        $mockBackups = [
            (object) ['id' => 3, 'filename' => 'backup3.sql'],
            (object) ['id' => 2, 'filename' => 'backup2.sql'],
            (object) ['id' => 1, 'filename' => 'backup1.sql']
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn($mockBackups);

        $result = $this->repository->getAll();

        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals(1, $result[2]->id);
    }

    public function testAddWithLongAuthKeyAndCode(): void
    {
        $longAuthKey = str_repeat('a', 256);
        $longAuthCode = str_repeat('b', 256);

        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->repository->add(
            'backup.sql',
            $longAuthKey,
            $longAuthCode,
            '2025-12-22 10:00:00'
        );

        $this->assertTrue($result);
    }

    public function testFindByFilenameWithSpecialCharacters(): void
    {
        $this->mockDb->method('escape')->willReturnCallback(function ($input) {
            return str_replace("'", "\\'", $input);
        });
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->repository->findByFilename("backup's file.sql");

        $this->assertNull($result);
    }
}
