<?php
/**
 * Test case for PMF_Search_Resultset
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   PMF_Tests
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-07-24
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultsetTest
 */
class ResultsetTest extends TestCase
{
    private $resultset;

    private $configuration;

    private $dbHandle;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        Strings::init('en');

        $this->dbHandle          = new Sqlite3();
        $this->configuration = new Configuration($this->dbHandle);

        $userMock = $this->getMockBuilder('phpMyFAQ\User\CurrentUser')->disableOriginalConstructor()->getMock();
        $faqMock  = $this->getMockBuilder('phpMyFAQ\Faq')->disableOriginalConstructor()->getMock();
        
        $this->resultset = new SearchResultSet($userMock, $faqMock, $this->configuration);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->resultset = null;
        parent::tearDown();
    }

    public function testSetAndGetNumberOfResults()
    {
        $this->resultset->setNumberOfResults(array(1,2));
        $this->assertEquals($this->resultset->getNumberOfResults(), 2);
        $this->resultset->setNumberOfResults(array());
        $this->assertEquals($this->resultset->getNumberOfResults(), 0);
    }
}
