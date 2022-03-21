<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Class ApiTest
 *
 * @testdox The phpMyFAQ API should
 * @package phpMyFAQ
 */
class ApiTest extends TestCase
{

    /** @var Configuration */
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
    }

    /**
     * @testdox return the available versions
     */
    public function testGetVersions(): void
    {
        $mockedApi = $this->getMockBuilder('phpMyFAQ\Api')->disableOriginalConstructor()->getMock();

        $versions = json_encode([
            'installed' => $this->configuration->get('main.currentVersion'),
            'current' => System::getVersion(),
            'next' => System::getVersion()
        ]);

        $mockedApi->expects($this->once())
            ->method('fetchData')
            ->with($this->equalTo('https://api.phpmyfaq.de/version'))
            ->willReturn($versions);

        $response = $mockedApi->fetchData('https://api.phpmyfaq.de/version');

        $this->assertEquals($versions, $response);
    }

    /**
     * @testdox return the current verification hashes
     */
    public function testGetVerificationIssues(): void
    {
        $mockedApi = $this->getMockBuilder('phpMyFAQ\Api')->disableOriginalConstructor()->getMock();

        $verifications = json_encode(['foo']);

        $mockedApi->expects($this->once())
            ->method('fetchData')
            ->with($this->equalTo('https://api.phpmyfaq.de/verify/' . System::getVersion()))
            ->willReturn($verifications);

        $response = $mockedApi->fetchData('https://api.phpmyfaq.de/verify/' . System::getVersion());

        $this->assertEquals($verifications, $response);
    }
}
