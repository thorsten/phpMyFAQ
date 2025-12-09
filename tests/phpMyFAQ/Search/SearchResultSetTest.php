<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
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
        $faqPermissionMock = $this->getMockBuilder('phpMyFAQ\Faq\Permission')->disableOriginalConstructor()->getMock();

        $this->SearchResultSet = new SearchResultSet($userMock, $faqPermissionMock, $configuration);
    }

    protected function tearDown(): void
    {
        $this->SearchResultSet = null;
        parent::tearDown();
    }

    public function testSetAndGetNumberOfResults(): void
    {
        $this->SearchResultSet->setNumberOfResults(array(1, 2));
        $this->assertEquals($this->SearchResultSet->getNumberOfResults(), 2);
        $this->SearchResultSet->setNumberOfResults(array());
        $this->assertEquals($this->SearchResultSet->getNumberOfResults(), 0);
    }
}
