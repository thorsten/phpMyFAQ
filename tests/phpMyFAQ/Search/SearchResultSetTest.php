<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultsetTest
 */
#[AllowMockObjectsWithoutExpectations]
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

        $userMock = $this->createMock(\phpMyFAQ\User\CurrentUser::class);
        $faqPermissionMock = $this->createMock(\phpMyFAQ\Faq\Permission::class);

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
