<?php


namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Date;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LatestUsersTest extends TestCase
{
    private LatestUsers $latestUsers;
    private Configuration|MockObject $configurationMock;
    private DatabaseDriver|MockObject $databaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->latestUsers = new LatestUsers($this->configurationMock);
    }

    public function testGetListReturnsEmptyArrayWhenNoUsersFound(): void
    {
        $resultResource = new \stdClass();

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->isString(), 0, 5)
            ->willReturn($resultResource);

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturnCallback(static function ($resource) use ($resultResource) {
                self::assertSame($resultResource, $resource);
                return false;
            });

        $result = $this->latestUsers->getList();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetListReturnsSingleUserWithConvertedDate(): void
    {
        $limit = 10;
        $resultResource = new \stdClass();

        $row = [
            'user_id' => '42',
            'login' => 'jane',
            'display_name' => 'Jane Doe',
            'member_since' => '20231225143000',
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->isString(), 0, $limit)
            ->willReturn($resultResource);

        $callCount = 0;
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnCallback(static function ($resource) use ($resultResource, $row, &$callCount) {
                self::assertSame($resultResource, $resource);

                $callCount++;
                if ($callCount === 1) {
                    return $row;
                }

                return false;
            });

        $result = $this->latestUsers->getList($limit);

        $this->assertCount(1, $result);
        $this->assertSame(42, $result[0]['id']);
        $this->assertSame('jane', $result[0]['login']);
        $this->assertSame('Jane Doe', $result[0]['display_name']);

        $expectedDate = Date::createIsoDate('20231225143000');
        $this->assertSame($expectedDate, $result[0]['member_since_iso']);
    }

    public function testGetListReturnsMultipleUsers(): void
    {
        $resultResource = new \stdClass();

        $rows = [
            [
                'user_id' => '1',
                'login' => 'alice',
                'display_name' => 'Alice',
                'member_since' => '20240101120000',
            ],
            [
                'user_id' => '2',
                'login' => 'bob',
                'display_name' => 'Bob',
                'member_since' => '20231231235959',
            ],
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->isString(), 0, 5)
            ->willReturn($resultResource);

        $callCount = 0;
        $this->databaseMock
            ->expects($this->exactly(3))
            ->method('fetchArray')
            ->willReturnCallback(static function ($resource) use ($resultResource, $rows, &$callCount) {
                self::assertSame($resultResource, $resource);

                $callCount++;
                if ($callCount === 1) {
                    return $rows[0];
                }

                if ($callCount === 2) {
                    return $rows[1];
                }

                return false;
            });

        $result = $this->latestUsers->getList();

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('alice', $result[0]['login']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertSame('bob', $result[1]['login']);
    }

    public function testGetListUsesGivenLimit(): void
    {
        $limit = 3;
        $resultResource = new \stdClass();

        $row = [
            'user_id' => '1',
            'login' => 'alice',
            'display_name' => 'Alice',
            'member_since' => '20240101120000',
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->isString(), 0, $limit)
            ->willReturn($resultResource);

        $callCount = 0;
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnCallback(static function ($resource) use ($resultResource, $row, &$callCount) {
                self::assertSame($resultResource, $resource);

                $callCount++;
                if ($callCount === 1) {
                    return $row;
                }

                return false;
            });

        $result = $this->latestUsers->getList($limit);

        $this->assertCount(1, $result);
    }

    public function testGetListSetsEmptyMemberSinceIsoWhenSourceIsEmpty(): void
    {
        $resultResource = new \stdClass();

        $row = [
            'user_id' => '1',
            'login' => 'alice',
            'display_name' => 'Alice',
            'member_since' => null,
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->isString(), 0, 5)
            ->willReturn($resultResource);

        $callCount = 0;
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnCallback(static function ($resource) use ($resultResource, $row, &$callCount) {
                self::assertSame($resultResource, $resource);

                $callCount++;
                if ($callCount === 1) {
                    return $row;
                }

                return false;
            });

        $result = $this->latestUsers->getList();

        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]['member_since_iso']);
    }
}

