<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class SessionRepositoryTest extends TestCase
{
    private SessionRepository $repository;
    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        $this->mockConfiguration = $this->createStub(Configuration::class);
        $this->mockDb = $this->createMock(DatabaseDriver::class);
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);

        $this->repository = new SessionRepository($this->mockConfiguration);
    }

    public function testCountOnlineUsersFromSessionsReturnsCount(): void
    {
        $minTimestamp = 1609459200;
        $expectedCount = 5;

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->cnt = $expectedCount;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $actualCount = $this->repository->countOnlineUsersFromSessions($minTimestamp);

        $this->assertEquals($expectedCount, $actualCount);
    }

    public function testCountOnlineUsersFromSessionsReturnsZeroWhenNoResult(): void
    {
        $minTimestamp = 1609459200;

        $this->mockDb->method('query')->willReturn(false);

        $actualCount = $this->repository->countOnlineUsersFromSessions($minTimestamp);

        $this->assertEquals(0, $actualCount);
    }

    public function testCountOnlineUsersFromFaqUserReturnsCount(): void
    {
        $minTimestamp = 1609459200;
        $expectedCount = 3;

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->cnt = $expectedCount;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $actualCount = $this->repository->countOnlineUsersFromFaqUser($minTimestamp);

        $this->assertEquals($expectedCount, $actualCount);
    }

    public function testGetTimeBySessionIdReturnsTimestamp(): void
    {
        $sessionId = 123;
        $expectedTime = 1609459200;

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->time = $expectedTime;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $actualTime = $this->repository->getTimeBySessionId($sessionId);

        $this->assertEquals($expectedTime, $actualTime);
    }

    public function testGetTimeBySessionIdReturnsZeroWhenNoResult(): void
    {
        $sessionId = 999;

        $this->mockDb->method('query')->willReturn(false);

        $actualTime = $this->repository->getTimeBySessionId($sessionId);

        $this->assertEquals(0, $actualTime);
    }

    public function testGetSessionsByDateRangeReturnsArray(): void
    {
        $firstHour = 1609459200;
        $lastHour = 1609545600;

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->sid = 1;
        $resultMock->ip = '127.0.0.1';
        $resultMock->time = 1609462800;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturnOnConsecutiveCalls($resultMock, false);

        $sessions = $this->repository->getSessionsByDateRange($firstHour, $lastHour);

        $this->assertCount(1, $sessions);
        $this->assertEquals(1, $sessions[0]->sid);
        $this->assertEquals('127.0.0.1', $sessions[0]->ip);
        $this->assertEquals(1609462800, $sessions[0]->time);
    }

    public function testGetSessionsByDateRangeReturnsEmptyArrayWhenNoResults(): void
    {
        $firstHour = 1609459200;
        $lastHour = 1609545600;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn(false);

        $sessions = $this->repository->getSessionsByDateRange($firstHour, $lastHour);

        $this->assertEmpty($sessions);
    }

    public function testCountTotalSessionsReturnsCount(): void
    {
        $expectedCount = 42;

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->num_sessions = $expectedCount;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $actualCount = $this->repository->countTotalSessions();

        $this->assertEquals($expectedCount, $actualCount);
    }

    public function testCountTotalSessionsReturnsZeroWhenNoResult(): void
    {
        $this->mockDb->method('query')->willReturn(false);

        $actualCount = $this->repository->countTotalSessions();

        $this->assertEquals(0, $actualCount);
    }

    public function testDeleteSessionsByTimeRangeReturnsTrue(): void
    {
        $first = 1609459200;
        $last = 1609545600;

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->repository->deleteSessionsByTimeRange($first, $last);

        $this->assertTrue($result);
    }

    public function testDeleteSessionsByTimeRangeReturnsFalse(): void
    {
        $first = 1609459200;
        $last = 1609545600;

        $this->mockDb->method('query')->willReturn(false);

        $result = $this->repository->deleteSessionsByTimeRange($first, $last);

        $this->assertFalse($result);
    }

    public function testDeleteAllSessionsReturnsTrue(): void
    {
        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->repository->deleteAllSessions();

        $this->assertTrue($result);
    }

    public function testDeleteAllSessionsReturnsFalse(): void
    {
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->repository->deleteAllSessions();

        $this->assertFalse($result);
    }

    public function testGetSessionTimestampsReturnsArray(): void
    {
        $startDate = 1609459200;
        $endDate = 1609545600;

        $resultMock1 = $this->createStub(stdClass::class);
        $resultMock1->time = 1609462800;

        $resultMock2 = $this->createStub(stdClass::class);
        $resultMock2->time = 1609476000;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturnOnConsecutiveCalls($resultMock1, $resultMock2, false);

        $timestamps = $this->repository->getSessionTimestamps($startDate, $endDate);

        $this->assertCount(2, $timestamps);
        $this->assertEquals(1609462800, $timestamps[0]);
        $this->assertEquals(1609476000, $timestamps[1]);
    }

    public function testGetSessionTimestampsReturnsEmptyArrayWhenNoResults(): void
    {
        $startDate = 1609459200;
        $endDate = 1609545600;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn(false);

        $timestamps = $this->repository->getSessionTimestamps($startDate, $endDate);

        $this->assertEmpty($timestamps);
    }

    public function testCountOnlineUsersFromSessionsVerifiesQuery(): void
    {
        $minTimestamp = 1609459200;

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function ($query) {
                $this->assertStringContainsString('SELECT COUNT(DISTINCT user_id)', $query);
                $this->assertStringContainsString('faqsessions', $query);
                $this->assertStringContainsString('WHERE time >=', $query);
                $this->assertStringContainsString('user_id > 0', $query);
                return true;
            });

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->cnt = 5;
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $this->repository->countOnlineUsersFromSessions($minTimestamp);
    }

    public function testCountOnlineUsersFromFaqUserVerifiesQuery(): void
    {
        $minTimestamp = 1609459200;

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function ($query) {
                $this->assertStringContainsString('SELECT COUNT(*)', $query);
                $this->assertStringContainsString('faquser', $query);
                $this->assertStringContainsString('session_id IS NOT NULL', $query);
                $this->assertStringContainsString('session_timestamp >=', $query);
                $this->assertStringContainsString('success = 1', $query);
                return true;
            });

        $resultMock = $this->createStub(stdClass::class);
        $resultMock->cnt = 3;
        $this->mockDb->method('fetchObject')->willReturn($resultMock);

        $this->repository->countOnlineUsersFromFaqUser($minTimestamp);
    }
}
