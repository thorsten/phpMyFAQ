<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Administration\Session;
use phpMyFAQ\Date;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class StatisticsHelperTest extends TestCase
{
    private StatisticsHelper $statisticsHelper;
    private Session $sessionMock;
    private Visits $visitsMock;
    private Date $dateMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->visitsMock = $this->createMock(Visits::class);
        $this->dateMock = $this->createMock(Date::class);

        // ensure Translation is initialized for calls to Translation::get in helper
        Translation::create();

        $this->statisticsHelper = new StatisticsHelper(
            $this->sessionMock,
            $this->visitsMock,
            $this->dateMock
        );

        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
    }

    public function testConstructor(): void
    {
        $helper = new StatisticsHelper(
            $this->sessionMock,
            $this->visitsMock,
            $this->dateMock
        );

        $this->assertInstanceOf(StatisticsHelper::class, $helper);
    }

    public function testGetTrackingFilesStatisticsStructure(): void
    {
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturn(0);

        $result = $this->statisticsHelper->getTrackingFilesStatistics();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertObjectHasProperty('numberOfDays', $result);
        $this->assertObjectHasProperty('firstDate', $result);
        $this->assertObjectHasProperty('lastDate', $result);

        $this->assertIsInt($result->numberOfDays);
        $this->assertIsInt($result->firstDate);
        $this->assertIsInt($result->lastDate);
    }

    public function testGetTrackingFilesStatisticsWithMockedValidDates(): void
    {
        $callCount = 0;
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount <= 3) {
                    return 1704067200 + ($callCount * 86400);
                }
                return 0;
            });

        $result = $this->statisticsHelper->getTrackingFilesStatistics();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertGreaterThan(0, $result->numberOfDays);
        $this->assertGreaterThan(0, $result->lastDate);
    }

    public function testGetAllTrackingDatesStructure(): void
    {
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturnCallback(function ($filename) {
                if (strlen($filename) === 16 && str_starts_with($filename, 'tracking')) {
                    return 1704067200;
                }
                return 0;
            });

        $result = $this->statisticsHelper->getAllTrackingDates();

        $this->assertIsArray($result);
        $this->assertContainsOnlyInt($result);

        $sortedResult = $result;
        sort($sortedResult);
        $this->assertEquals($sortedResult, $result);
    }

    public function testDeleteTrackingFilesBasicBehavior(): void
    {
        $month = '012024';

        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturnCallback(function ($filename) use ($month) {
                if (strpos($filename, 'tracking') === 0 && strpos($filename, $month) !== false) {
                    return 1704067200;
                }
                return 0;
            });

        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateEnd')
            ->willReturnCallback(function ($filename) use ($month) {
                if (strpos($filename, 'tracking') === 0 && strpos($filename, $month) !== false) {
                    return 1704153599;
                }
                return 0;
            });

        $this->sessionMock->expects($this->once())
            ->method('deleteSessions')
            ->willReturn(true);

        $result = $this->statisticsHelper->deleteTrackingFiles($month);

        $this->assertIsBool($result);
    }

    public function testClearAllVisitsBasicBehavior(): void
    {
        $this->visitsMock->expects($this->once())
            ->method('resetAll');

        $this->sessionMock->expects($this->once())
            ->method('deleteAllSessions')
            ->willReturn(true);

        $result = $this->statisticsHelper->clearAllVisits();

        $this->assertIsBool($result);
    }

    public function testRenderMonthSelectorStructure(): void
    {
        // This test remains unchanged as it relies on getAllTrackingDates
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturn(1704067200);

        $result = $this->statisticsHelper->renderMonthSelector();
        $this->assertIsString($result);
    }

    public function testRenderDaySelectorStructure(): void
    {
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturn(1704067200);

        $result = $this->statisticsHelper->renderDaySelector();

        $this->assertIsString($result);
    }

    /**
     * Test all public methods exist and are callable
     */
    public function testAllPublicMethodsExist(): void
    {
        $reflection = new ReflectionClass(StatisticsHelper::class);

        $expectedMethods = [
            'getTrackingFilesStatistics',
            'getFirstTrackingDate',
            'getLastTrackingDate',
            'getAllTrackingDates',
            'deleteTrackingFiles',
            'clearAllVisits',
            'renderMonthSelector',
            'renderDaySelector'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Method $methodName should exist"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    /**
     * Test basic return types for all methods
     */
    public function testMethodReturnTypes(): void
    {
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturn(1704067200);

        $this->dateMock->expects($this->any())
            ->method('format')
            ->willReturn('2024-01-01 12:00');

        $this->sessionMock->expects($this->any())
            ->method('deleteSessions')
            ->willReturn(true);

        $this->sessionMock->expects($this->any())
            ->method('deleteAllSessions')
            ->willReturn(true);

        $_SERVER['REQUEST_TIME'] = time();

        $stats = $this->statisticsHelper->getTrackingFilesStatistics();
        $this->assertInstanceOf(stdClass::class, $stats);

        $dates = $this->statisticsHelper->getAllTrackingDates();
        $this->assertIsArray($dates);

        $deleteResult = $this->statisticsHelper->deleteTrackingFiles('012024');
        $this->assertIsBool($deleteResult);

        $clearResult = $this->statisticsHelper->clearAllVisits();
        $this->assertIsBool($clearResult);

        $daySelector = $this->statisticsHelper->renderDaySelector();
        $this->assertIsString($daySelector);
    }

    /**
     * Test edge case handling
     */
    public function testEdgeCaseHandling(): void
    {
        $this->dateMock->expects($this->any())
            ->method('getTrackingFileDateStart')
            ->willReturn(0);

        $result = $this->statisticsHelper->getTrackingFilesStatistics();
        $this->assertInstanceOf(stdClass::class, $result);

        $dates = $this->statisticsHelper->getAllTrackingDates();
        $this->assertIsArray($dates);
    }

    /**
     * Test readonly class behavior
     */
    public function testReadonlyClassBehavior(): void
    {
        $reflection = new ReflectionClass(StatisticsHelper::class);

        if (method_exists($reflection, 'isReadOnly')) {
            $this->assertTrue(
                $reflection->isReadOnly(),
                'StatisticsHelper should be a readonly class'
            );
        }

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertEquals(3, $constructor->getNumberOfParameters());
    }

    /**
     * Test dependency injection
     */
    public function testDependencyInjection(): void
    {
        $session = $this->createStub(Session::class);
        $visits = $this->createStub(Visits::class);
        $date = $this->createStub(Date::class);

        $helper = new StatisticsHelper($session, $visits, $date);
        $this->assertInstanceOf(StatisticsHelper::class, $helper);

        $reflection = new ReflectionClass($helper);
        $properties = $reflection->getProperties();

        $this->assertCount(3, $properties, 'Should have 3 private properties for dependencies');
    }
}
