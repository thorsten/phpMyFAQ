<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Administration\Session;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Drives the file-based statistics against a temporary tracking directory with real tracking
 * file names, so the listing, the reads and the deletions all address the same directory.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(StatisticsHelper::class)]
#[UsesNamespace('phpMyFAQ')]
final class StatisticsHelperTrackingDirectoryTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private string $trackingDirectory;

    private Session&\PHPUnit\Framework\MockObject\MockObject $session;

    private Visits&\PHPUnit\Framework\MockObject\MockObject $visits;

    private StatisticsHelper $helper;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->trackingDirectory = sys_get_temp_dir() . '/pmf-tracking-' . bin2hex(random_bytes(6));
        mkdir($this->trackingDirectory, 0o755, true);

        $this->session = $this->createMock(Session::class);
        $this->visits = $this->createMock(Visits::class);
        $this->helper = new StatisticsHelper(
            $this->session,
            $this->visits,
            new Date($this->configuration),
            $this->trackingDirectory,
        );
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->trackingDirectory);
    }

    /**
     * Writes a tracking file named trackingDDMMYYYY with one visit row whose eighth field is
     * the visit timestamp, as the tracking writer records it.
     */
    private function writeTrackingFile(string $dayMonthYear, int $visitTimestamp): string
    {
        $path = $this->trackingDirectory . '/tracking' . $dayMonthYear;
        file_put_contents($path, sprintf("1;faq;0;en;127.0.0.1;Mozilla;;%d\n", $visitTimestamp));

        return $path;
    }

    public function testStatisticsCoverTheOldestAndNewestTrackingFile(): void
    {
        $this->writeTrackingFile('05012024', 1704412800);
        $this->writeTrackingFile('20022024', 1708387200);
        $this->writeTrackingFile('10012024', 1704844800);
        file_put_contents($this->trackingDirectory . '/.htaccess', 'Deny from all');

        $statistics = $this->helper->getTrackingFilesStatistics();

        $this->assertSame(4, $statistics->numberOfDays, 'Every entry of the directory is counted');
        $this->assertSame((int) gmmktime(0, 0, 0, 1, 5, 2024), $statistics->firstDate);
        $this->assertSame((int) gmmktime(0, 0, 0, 2, 20, 2024), $statistics->lastDate);

        $this->assertEqualsCanonicalizing([
            (int) gmmktime(0, 0, 0, 1, 5, 2024),
            (int) gmmktime(0, 0, 0, 1, 10, 2024),
            (int) gmmktime(0, 0, 0, 2, 20, 2024),
        ], $this->helper->getAllTrackingDates());
    }

    public function testFirstAndLastTrackingDatesComeFromTheFilesThemselves(): void
    {
        $first = (int) gmmktime(0, 0, 0, 1, 5, 2024);
        $last = (int) gmmktime(0, 0, 0, 2, 20, 2024);
        $this->writeTrackingFile(date('dmY', $first), 1704412800);
        $this->writeTrackingFile(date('dmY', $last), 1708387200);

        $this->assertSame(date('Y-m-d H:i', 1704412800), substr($this->helper->getFirstTrackingDate($first), 0, 16));
        $this->assertSame(date('Y-m-d H:i', 1708387200), substr($this->helper->getLastTrackingDate($last), 0, 16));
        $this->assertSame(
            Translation::getString('ad_sess_noentry'),
            $this->helper->getFirstTrackingDate((int) gmmktime(0, 0, 0, 3, 1, 2024)),
        );
    }

    public function testDeletingAMonthRemovesOnlyThatMonthsFilesAndItsSessions(): void
    {
        $january5 = $this->writeTrackingFile('05012024', 1704412800);
        $january10 = $this->writeTrackingFile('10012024', 1704844800);
        $february = $this->writeTrackingFile('20022024', 1708387200);

        $this->session
            ->expects($this->once())
            ->method('deleteSessions')
            ->with((int) gmmktime(0, 0, 0, 1, 5, 2024), (int) gmmktime(23, 59, 59, 1, 10, 2024))
            ->willReturn(true);

        $this->assertTrue($this->helper->deleteTrackingFiles('012024'));

        $this->assertFileDoesNotExist($january5);
        $this->assertFileDoesNotExist($january10);
        $this->assertFileExists($february);
    }

    public function testClearingAllVisitsRemovesEveryFileAndResetsTheCounters(): void
    {
        $this->writeTrackingFile('05012024', 1704412800);
        $this->writeTrackingFile('20022024', 1708387200);
        mkdir($this->trackingDirectory . '/keep');

        $this->visits->expects($this->once())->method('resetAll');
        $this->session->expects($this->once())->method('deleteAllSessions')->willReturn(true);

        $this->assertTrue($this->helper->clearAllVisits());

        $this->assertSame([$this->trackingDirectory . '/keep'], glob($this->trackingDirectory . '/*'));
    }

    public function testAMissingTrackingDirectoryYieldsEmptyStatistics(): void
    {
        $helper = new StatisticsHelper(
            $this->session,
            $this->visits,
            new Date($this->configuration),
            $this->trackingDirectory . '/does-not-exist',
        );

        set_error_handler(static fn(): bool => true);
        try {
            $statistics = $helper->getTrackingFilesStatistics();
            $this->assertSame(0, $statistics->numberOfDays);
            $this->assertSame([], $helper->getAllTrackingDates());
            $this->assertFalse($helper->deleteTrackingFiles('012024'));
        } finally {
            restore_error_handler();
        }
    }
}
