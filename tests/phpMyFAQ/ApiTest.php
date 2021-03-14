<?php

/**
 * Api Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2021-03-14
 */

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
    private $configuration;

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
    public function testGetVersions()
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
    public function testGetVerificationIssues()
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
