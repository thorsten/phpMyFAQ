<?php
/**
 * Test case for PMF_Search_Resultset
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
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
class SearchResultSetTest extends TestCase
{
    /** @var SearchResultSet */
    private $SearchResultSet;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);

        $userMock = $this->getMockBuilder('phpMyFAQ\User\CurrentUser')->disableOriginalConstructor()->getMock();
        $faqPermissionMock = $this->getMockBuilder('phpMyFAQ\Faq\FaqPermission')->disableOriginalConstructor()->getMock(
        );

        $this->SearchResultSet = new SearchResultSet($userMock, $faqPermissionMock, $configuration);
    }

    protected function tearDown(): void
    {
        $this->SearchResultSet = null;
        parent::tearDown();
    }

    public function testSetAndGetNumberOfResults()
    {
        $this->SearchResultSet->setNumberOfResults(array(1, 2));
        $this->assertEquals($this->SearchResultSet->getNumberOfResults(), 2);
        $this->SearchResultSet->setNumberOfResults(array());
        $this->assertEquals($this->SearchResultSet->getNumberOfResults(), 0);
    }
}
