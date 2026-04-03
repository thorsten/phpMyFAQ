<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Administration\Session;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;

#[AllowMockObjectsWithoutExpectations]
class StatisticsHelperTest extends TestCase
{
    private StatisticsHelper $statisticsHelper;
    private Session $sessionMock;
    private Visits $visitsMock;
    private Date $dateMock;
    private array $createdTrackingFiles = [];
    private ?string $databaseFile = null;
    private mixed $previousConfigurationInstance = null;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->visitsMock = $this->createMock(Visits::class);
        $this->dateMock = $this->createMock(Date::class);

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-statistics-helper-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);
        $db = new Sqlite3();
        $db->connect($this->databaseFile, '', '');
        $configuration = new Configuration($db);
        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $this->previousConfigurationInstance = $property->getValue();

        $language = new Language($configuration, $this->createStub(HttpSession::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);
        $property->setValue(null, $configuration);

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->statisticsHelper = new StatisticsHelper($this->sessionMock, $this->visitsMock, $this->dateMock);

        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTrackingFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->createdTrackingFiles = [];

        if ($this->databaseFile !== null && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, $this->previousConfigurationInstance);

        $_SERVER = [];
    }

    public function testConstructor(): void
    {
        $helper = new StatisticsHelper($this->sessionMock, $this->visitsMock, $this->dateMock);

        $this->assertInstanceOf(StatisticsHelper::class, $helper);
    }

    public function testGetTrackingFilesStatisticsStructure(): void
    {
        $this->dateMock->method('getTrackingFileDateStart')->willReturn(0);

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
        $this->dateMock
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
        $this->dateMock
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

        $this->dateMock
            ->method('getTrackingFileDateStart')
            ->willReturnCallback(function ($filename) use ($month) {
                if (strpos($filename, 'tracking') === 0 && strpos($filename, $month) !== false) {
                    return 1704067200;
                }
                return 0;
            });

        $this->dateMock
            ->method('getTrackingFileDateEnd')
            ->willReturnCallback(function ($filename) use ($month) {
                if (strpos($filename, 'tracking') === 0 && strpos($filename, $month) !== false) {
                    return 1704153599;
                }
                return 0;
            });

        $this->sessionMock->expects($this->once())->method('deleteSessions')->willReturn(true);

        $result = $this->statisticsHelper->deleteTrackingFiles($month);

        $this->assertIsBool($result);
    }

    public function testClearAllVisitsBasicBehavior(): void
    {
        $this->visitsMock->expects($this->once())->method('resetAll');

        $this->sessionMock->expects($this->once())->method('deleteAllSessions')->willReturn(true);

        $result = $this->statisticsHelper->clearAllVisits();

        $this->assertIsBool($result);
    }

    public function testRenderMonthSelectorStructure(): void
    {
        // This test remains unchanged as it relies on getAllTrackingDates
        $this->dateMock->method('getTrackingFileDateStart')->willReturn(1704067200);

        $result = $this->statisticsHelper->renderMonthSelector();
        $this->assertIsString($result);
    }

    public function testRenderDaySelectorStructure(): void
    {
        $this->dateMock->method('getTrackingFileDateStart')->willReturn(1704067200);

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
            'renderDaySelector',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method $methodName should exist");

            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method $methodName should be public");
        }
    }

    /**
     * Test basic return types for all methods
     */
    public function testMethodReturnTypes(): void
    {
        $this->dateMock->method('getTrackingFileDateStart')->willReturn(1704067200);

        $this->dateMock->method('format')->willReturn('2024-01-01 12:00');

        $this->sessionMock->method('deleteSessions')->willReturn(true);

        $this->sessionMock->method('deleteAllSessions')->willReturn(true);

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
        $this->dateMock->method('getTrackingFileDateStart')->willReturn(0);

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
            $this->assertTrue($reflection->isReadOnly(), 'StatisticsHelper should be a readonly class');
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

    public function testGetFirstTrackingDateReturnsFormattedDate(): void
    {
        $_SERVER['REQUEST_TIME'] = 1700000000;
        $timestamp = mktime(0, 0, 0, 1, 15, 2099);
        $file = $this->createTrackingFile($timestamp, [
            'a;b;c;d;e;f;g;1705276800',
        ]);

        $this->dateMock
            ->expects($this->once())
            ->method('format')
            ->with('2024-01-15 01:00')
            ->willReturn('formatted-first');

        $this->assertSame('formatted-first', $this->statisticsHelper->getFirstTrackingDate($timestamp));
        $this->assertFileExists($file);
    }

    public function testGetFirstTrackingDateReturnsNoEntryForMissingFile(): void
    {
        $timestamp = mktime(0, 0, 0, 1, 17, 2099);

        $this->assertSame(
            Translation::get(key: 'ad_sess_noentry'),
            $this->statisticsHelper->getFirstTrackingDate($timestamp),
        );
    }

    public function testGetLastTrackingDateReturnsFormattedDateFromLastRow(): void
    {
        $_SERVER['REQUEST_TIME'] = 1700000000;
        $timestamp = mktime(0, 0, 0, 1, 16, 2099);
        $this->createTrackingFile($timestamp, [
            'a;b;c;d;e;f;g;1705276800',
            'a;b;c;d;e;f;g;1705363200',
        ]);

        $this->dateMock
            ->expects($this->once())
            ->method('format')
            ->with('2024-01-16 01:00')
            ->willReturn('formatted-last');

        $this->assertSame('formatted-last', $this->statisticsHelper->getLastTrackingDate($timestamp));
    }

    public function testGetLastTrackingDateFallsBackToRequestTimeWhenFileHasNoRows(): void
    {
        $_SERVER['REQUEST_TIME'] = 1700000000;
        $timestamp = mktime(0, 0, 0, 1, 18, 2099);
        $this->createTrackingFile($timestamp, []);

        $this->dateMock
            ->expects($this->once())
            ->method('format')
            ->with('2023-11-14 23:13')
            ->willReturn('formatted-request-time');

        $this->assertSame('formatted-request-time', $this->statisticsHelper->getLastTrackingDate($timestamp));
    }

    public function testGetLastTrackingDateReturnsNoEntryForMissingFile(): void
    {
        $timestamp = mktime(0, 0, 0, 1, 19, 2099);

        $this->assertSame(
            Translation::get(key: 'ad_sess_noentry'),
            $this->statisticsHelper->getLastTrackingDate($timestamp),
        );
    }

    public function testGetLastTrackingDateFallsBackWhenRequestTimeIsZero(): void
    {
        $_SERVER['REQUEST_TIME'] = 0;
        $timestamp = mktime(0, 0, 0, 1, 20, 2099);
        $this->createTrackingFile($timestamp, [
            'a;b;c;d;e;f;g;invalid',
        ]);

        $this->dateMock
            ->expects($this->once())
            ->method('format')
            ->with('1970-01-01 01:00')
            ->willReturn('formatted-zero-request-time');

        $this->assertSame('formatted-zero-request-time', $this->statisticsHelper->getLastTrackingDate($timestamp));
    }

    public function testRenderDaySelectorReturnsFallbackWhenNoTrackingDatesExist(): void
    {
        $helper = $this
            ->getMockBuilder(StatisticsHelper::class)
            ->setConstructorArgs([$this->sessionMock, $this->visitsMock, $this->dateMock])
            ->onlyMethods(['getAllTrackingDates'])
            ->getMock();

        $helper->method('getAllTrackingDates')->willReturn([]);

        $result = $helper->renderDaySelector();

        $this->assertStringContainsString(Translation::get(key: 'ad_stat_choose'), $result);
        $this->assertStringContainsString('selected', $result);
    }

    public function testRenderDaySelectorMarksCurrentDayAsSelected(): void
    {
        $_SERVER['REQUEST_TIME'] = 1705276800;
        $helper = $this
            ->getMockBuilder(StatisticsHelper::class)
            ->setConstructorArgs([$this->sessionMock, $this->visitsMock, $this->dateMock])
            ->onlyMethods(['getAllTrackingDates'])
            ->getMock();

        $helper->method('getAllTrackingDates')->willReturn([1705276800, 1705363200]);

        $this->dateMock->method('format')->willReturnCallback(static fn(string $date): string => 'formatted-' . $date);

        $result = $helper->renderDaySelector();

        $this->assertStringContainsString('<option value="1705276800" selected>', $result);
        $this->assertStringContainsString('formatted-2024-01-15 01:00', $result);
        $this->assertStringContainsString('<option value="1705363200">', $result);
    }

    private function createTrackingFile(int $timestamp, array $rows): string
    {
        $file = PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $timestamp);
        file_put_contents($file, implode(PHP_EOL, $rows));
        $this->createdTrackingFiles[] = $file;

        return $file;
    }
}
