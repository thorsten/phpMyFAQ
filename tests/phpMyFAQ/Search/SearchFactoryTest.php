<?php
/**
 * Test case for PMF_Search_Database
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-07-06
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Search\SearchFactory;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchFactoryTest
 */
class SearchFactoryTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $dbHandle = new Sqlite3();
        $this->configuration = new Configuration($dbHandle);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->PMF_Search_Database = null;
        parent::tearDown();
    }

    public function testCreate()
    {
        $search = SearchFactory::create($this->configuration, array('database' => 'sqlite3'));

        //$this->assertEquals(new Sqlite3($this->configuration), $search);
        $this->assertInstanceOf('phpMyFAQ\Search\Database\Sqlite3', $search);
    }

}
