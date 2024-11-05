<?php

namespace phpMyFAQ\Helper;

use org\bovigo\vfs\vfsStreamDirectory;
use phpMyFAQ\Administration\Session;
use phpMyFAQ\Date;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class StatisticsHelperTest extends TestCase
{
    private Session $sessionMock;
    private Visits $visitsMock;
    private Date $dateMock;
    private vfsStreamDirectory $root;

    /**
     * @throws Exception|\phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->sessionMock = $this->createMock(Session::class);
        $this->visitsMock = $this->createMock(Visits::class);
        $this->dateMock = $this->createMock(Date::class);

        $this->root = vfsStream::setup('content/core/data');
    }

    public function testGetFirstTrackingDateWithNoFile(): void
    {
        $helper = new StatisticsHelper($this->sessionMock, $this->visitsMock, $this->dateMock);

        $result = $helper->getFirstTrackingDate(strtotime('2020-01-01'));

        $this->assertEquals('No entry', $result);
    }

    public function testClearAllVisits(): void
    {
        vfsStream::newFile('tracking01012020')->at($this->root);

        $this->visitsMock->expects($this->once())
            ->method('resetAll')
            ->willReturn(true);

        $this->sessionMock->expects($this->once())
            ->method('deleteAllSessions')
            ->willReturn(true);

        $helper = new StatisticsHelper($this->sessionMock, $this->visitsMock, $this->dateMock);

        $result = $helper->clearAllVisits();

        $this->assertTrue($result);
        $this->assertFileDoesNotExist(vfsStream::url('content/core/data/tracking01012020'));
    }
}
