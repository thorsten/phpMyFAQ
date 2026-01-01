<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class PermissionTest
 *
 * @package phpMyFAQ\Faq
 */
#[AllowMockObjectsWithoutExpectations]
class PermissionTest extends TestCase
{
    private Permission $permission;
    private Configuration $configuration;
    private Sqlite3 $dbMock;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        // Create database mock
        $this->dbMock = $this->createMock(Sqlite3::class);

        // Create configuration with mocked database
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->dbMock);

        $this->permission = new Permission($this->configuration);
    }

    // ===========================================
    // Constructor Tests
    // ===========================================

    public function testConstructorWithConfiguration(): void
    {
        $permission = new Permission($this->configuration);
        $this->assertInstanceOf(Permission::class, $permission);
    }

    // ===========================================
    // add() Method Tests - Critical Security Function
    // ===========================================

    public function testAddUserPermissionSuccessfully(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO'))
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, 123, [456]);
        $this->assertTrue($result);
    }

    public function testAddGroupPermissionSuccessfully(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO'))
            ->willReturn(true);

        $result = $this->permission->add(Permission::GROUP, 123, [789]);
        $this->assertTrue($result);
    }

    public function testAddMultipleIdsSuccessfully(): void
    {
        $this->dbMock
            ->expects($this->exactly(3))
            ->method('query')
            ->with($this->stringContains('INSERT INTO'))
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, 123, [456, 789, 101]);
        $this->assertTrue($result);
    }

    public function testAddWithInvalidModeReturnsFalse(): void
    {
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->add('invalid_mode', 123, [456]);
        $this->assertFalse($result);
    }

    public function testAddWithEmptyIdsArrayDoesNothing(): void
    {
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->add(Permission::USER, 123, []);
        $this->assertTrue($result);
    }

    public function testAddGeneratesCorrectUserQuery(): void
    {
        $expectedQuery = 'INSERT INTO faqdata_user (record_id, user_id) VALUES (123, 456)';

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('faqdata_user'))
            ->willReturn(true);

        $this->permission->add(Permission::USER, 123, [456]);
    }

    public function testAddGeneratesCorrectGroupQuery(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('faqdata_group'))
            ->willReturn(true);

        $this->permission->add(Permission::GROUP, 123, [456]);
    }

    // ===========================================
    // delete() Method Tests - Critical Security Function
    // ===========================================

    public function testDeleteUserPermissionSuccessfully(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->permission->delete(Permission::USER, 123);
        $this->assertTrue($result);
    }

    public function testDeleteGroupPermissionSuccessfully(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->permission->delete(Permission::GROUP, 123);
        $this->assertTrue($result);
    }

    public function testDeleteWithInvalidModeReturnsFalse(): void
    {
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->delete('invalid_mode', 123);
        $this->assertFalse($result);
    }

    public function testDeleteGeneratesCorrectUserQuery(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqdata_user WHERE record_id = 123'))
            ->willReturn(true);

        $this->permission->delete(Permission::USER, 123);
    }

    public function testDeleteGeneratesCorrectGroupQuery(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqdata_group WHERE record_id = 123'))
            ->willReturn(true);

        $this->permission->delete(Permission::GROUP, 123);
    }

    public function testDeleteWithDatabaseErrorReturnsFalse(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $result = $this->permission->delete(Permission::USER, 123);
        $this->assertFalse($result);
    }

    // ===========================================
    // get() Method Tests - Permission Retrieval
    // ===========================================

    public function testGetUserPermissionsSuccessfully(): void
    {
        // Create proper SQLite3Result mock
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT user_id'))
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(2);

        $permission1 = new \stdClass();
        $permission1->permission = '456';
        $permission2 = new \stdClass();
        $permission2->permission = '789';

        // The while loop continues until fetchObject returns null (not false)
        $this->dbMock
            ->expects($this->exactly(3))
            ->method('fetchObject')
            ->with($resultMock)
            ->willReturnOnConsecutiveCalls($permission1, $permission2, null);

        $result = $this->permission->get(Permission::USER, 123);
        $this->assertEquals([456, 789], $result);
    }

    public function testGetGroupPermissionsSuccessfully(): void
    {
        // Create proper SQLite3Result mock
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT group_id'))
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(1);

        $permission = new \stdClass();
        $permission->permission = '789';

        // The while loop continues until fetchObject returns null (not false)
        $this->dbMock
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->with($resultMock)
            ->willReturnOnConsecutiveCalls($permission, null);

        $result = $this->permission->get(Permission::GROUP, 123);
        $this->assertEquals([789], $result);
    }

    public function testGetWithInvalidModeReturnsEmptyArray(): void
    {
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->get('invalid_mode', 123);
        $this->assertEquals([], $result);
    }

    public function testGetWithFaqIdZeroReturnsMinusOne(): void
    {
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->get(Permission::USER, 0);
        $this->assertEquals([-1], $result);
    }

    public function testGetWithNoResultsReturnsEmptyArray(): void
    {
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(0);

        $this->dbMock->expects($this->never())->method('fetchObject');

        $result = $this->permission->get(Permission::USER, 123);
        $this->assertEquals([], $result);
    }

    public function testGetGeneratesCorrectUserQuery(): void
    {
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT user_id AS permission FROM faqdata_user WHERE record_id = 123'))
            ->willReturn($resultMock);

        $this->dbMock->method('numRows')->willReturn(0);

        $this->permission->get(Permission::USER, 123);
    }

    public function testGetGeneratesCorrectGroupQuery(): void
    {
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT group_id AS permission FROM faqdata_group WHERE record_id = 123'))
            ->willReturn($resultMock);

        $this->dbMock->method('numRows')->willReturn(0);

        $this->permission->get(Permission::GROUP, 123);
    }

    // ===========================================
    // Security Tests
    // ===========================================

    public function testAddWithNegativeFaqIdIsHandled(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('VALUES (-123, 456)'))
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, -123, [456]);
        $this->assertTrue($result);
    }

    public function testDeleteWithNegativeFaqIdIsHandled(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('WHERE record_id = -123'))
            ->willReturn(true);

        $result = $this->permission->delete(Permission::USER, -123);
        $this->assertTrue($result);
    }

    public function testGetWithNegativeFaqIdIsHandled(): void
    {
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('WHERE record_id = -123'))
            ->willReturn($resultMock);

        $this->dbMock->method('numRows')->willReturn(0);

        $result = $this->permission->get(Permission::USER, -123);
        $this->assertEquals([], $result);
    }

    // ===========================================
    // Edge Cases and Boundary Tests
    // ===========================================

    public function testAddWithLargeArrayOfIds(): void
    {
        $largeIdArray = range(1, 100);

        $this->dbMock
            ->expects($this->exactly(100))
            ->method('query')
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, 123, $largeIdArray);
        $this->assertTrue($result);
    }

    public function testConstantsAreDefinedCorrectly(): void
    {
        $this->assertEquals('user', Permission::USER);
        $this->assertEquals('group', Permission::GROUP);
    }

    public function testModeCaseSensitivity(): void
    {
        // Test uppercase mode strings
        $this->dbMock->expects($this->never())->method('query');

        $result = $this->permission->add('USER', 123, [456]);
        $this->assertFalse($result);

        $result = $this->permission->add('GROUP', 123, [456]);
        $this->assertFalse($result);
    }
}
