<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

class SessionTest extends TestCase
{
    private Configuration $configurationMock;
    private DatabaseDriver $databaseMock;
    private Session $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock->method('getDb')->willReturn($this->databaseMock);

        $this->session = new Session($this->configurationMock);
    }

    /**
     * @throws Exception
     */
    public function testGetTimeFromSessionId()
    {
        $sessionId = 123;
        $expectedTime = 1609459200; // Example timestamp

        $resultMock = $this->createMock(stdClass::class);
        $resultMock->time = $expectedTime;

        $this->databaseMock->method('query')->willReturn(true);
        $this->databaseMock->method('fetchObject')->willReturn($resultMock);

        $actualTime = $this->session->getTimeFromSessionId($sessionId);

        $this->assertEquals($expectedTime, $actualTime);
    }

    /**
     * @throws Exception
     */
    public function testGetSessionsByDate(): void
    {
        $firstHour = 1609459200;
        $lastHour = 1609545600;

        $resultMock = $this->createMock(stdClass::class);
        $resultMock->sid = 1;
        $resultMock->ip = '127.0.0.1';
        $resultMock->time = 1609462800;

        $this->databaseMock->method('query')->willReturn(true);
        $this->databaseMock->method('fetchObject')->willReturnOnConsecutiveCalls($resultMock, false);

        $expectedSessions = [
            1 => [
                'ip' => '127.0.0.1',
                'time' => 1609462800,
            ],
        ];

        $actualSessions = $this->session->getSessionsByDate($firstHour, $lastHour);

        $this->assertEquals($expectedSessions, $actualSessions);
    }

    /**
     * @throws Exception
     */
    public function testGetNumberOfSessions(): void
    {
        $expectedNumSessions = 5;

        $resultMock = $this->createMock(stdClass::class);
        $resultMock->num_sessions = $expectedNumSessions;

        $this->databaseMock->method('query')->willReturn(true);
        $this->databaseMock->method('fetchObject')->willReturn($resultMock);

        $actualNumSessions = $this->session->getNumberOfSessions();

        $this->assertEquals($expectedNumSessions, $actualNumSessions);
    }

    public function testDeleteSessions(): void
    {
        $first = 1609459200;
        $last = 1609545600;

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->session->deleteSessions($first, $last);

        $this->assertTrue($result);
    }

    public function testDeleteAllSessions(): void
    {
        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->session->deleteAllSessions();

        $this->assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testGetLast30DaysVisits()
    {
        $startDate = strtotime('-1 month');
        $endDate = time();

        $resultMock = $this->createMock(stdClass::class);
        $resultMock->time = $startDate + 86400; // Example timestamp within the range

        $this->databaseMock->method('query')->willReturn(true);
        $this->databaseMock->method('fetchObject')->willReturnOnConsecutiveCalls($resultMock, false);

        $actualVisits = $this->session->getLast30DaysVisits($endDate);

        $expectedVisits = [];
        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $visit = new stdClass();
            $visit->date = date('Y-m-d', $date);
            $visit->number = ($date == $startDate + 86400) ? 1 : 0;
            $expectedVisits[] = $visit;
        }

        $this->assertEquals($expectedVisits, $actualVisits);
    }
}
