<?php
/**
 * Test case for PMF_Search_Database
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db\Sqlite3;
use phpMyFAQ\Search\SearchDatabase;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * CategoryHelper test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-26
 */
class SearchDatabaseTest extends TestCase
{
    private $searchDatabase;
    private $configuration;
    private $dbHandle;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        Strings::init('en');

        $this->dbHandle = new Sqlite3();
        $this->configuration = new Configuration($this->dbHandle);
        $this->searchDatabase = new SearchDatabase($this->configuration);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->searchDatabase = null;
        parent::tearDown();
    }

    public function testSetAndGetTable()
    {
        $this->searchDatabase->setTable('faqdata');
        $this->assertEquals('faqdata', $this->searchDatabase->getTable());
        $this->assertInternalType('string', $this->searchDatabase->getTable());
    }

    public function testSetAndGetTableWithoutTable()
    {
        $this->assertEquals('', $this->searchDatabase->getTable());
    }

    public function testSetAndGetJoinedTable()
    {
        $this->searchDatabase->setJoinedTable('faqcategoryrelations');
        $this->assertEquals(' LEFT JOIN faqcategoryrelations ON ', $this->searchDatabase->getJoinedTable());
        $this->assertInternalType('string', $this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetJoinedTableWithoutJoinedTable()
    {
        $this->assertEquals('', $this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetResultColumns()
    {
        $resultColumns = array('faqdata.id AS id',
                               'faqdata.lang AS lang',
                               'faqdata.thema AS question',
                               'faqdata.content AS answer');

        $this->searchDatabase->setResultColumns($resultColumns);
        $this->assertEquals('faqdata.id AS id, faqdata.lang AS lang, faqdata.thema AS question, faqdata.content AS answer',
            $this->searchDatabase->getResultColumns());
        $this->assertInternalType('string',
            $this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetResultColumnsWithoutResultColumns()
    {
        $this->searchDatabase->setResultColumns(array());
        $this->assertEquals('', $this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetJoinedColumns()
    {
        $joinedColumns = array('faqdata.id = faqcategoryrelations.record_id',
                               'faqdata.lang = faqcategoryrelations.record_lang');

        $this->searchDatabase->setJoinedColumns($joinedColumns);
        $this->assertEquals('faqdata.id = faqcategoryrelations.record_id AND faqdata.lang = faqcategoryrelations.record_lang ',
            $this->searchDatabase->getJoinedColumns());
        $this->assertInternalType('string',
            $this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetJoinedColumnsWithoutJoinedColumns()
    {
        $this->searchDatabase->setJoinedColumns(array());
        $this->assertEquals('', $this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetMatchingColumns()
    {
        $matchingColumns = array('faqdata.thema',
                                 'faqdata.content',
                                 'faqdata.keywords');

        $this->searchDatabase->setMatchingColumns($matchingColumns);
        $this->assertEquals('faqdata.thema, faqdata.content, faqdata.keywords',
            $this->searchDatabase->getMatchingColumns());
        $this->assertInternalType('string',
            $this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetMatchingColumnsWithoutColums()
    {
        $this->searchDatabase->setMatchingColumns(array());
        $this->assertEquals('', $this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetConditions()
    {
        $conditions = array('faqdata.active' => "'yes'",
                            'faqcategoryrelations.category_id' => 1);

        $this->searchDatabase->setConditions($conditions);
        $this->assertEquals(" AND faqdata.active = 'yes' AND faqcategoryrelations.category_id = 1",
            $this->searchDatabase->getConditions());
        $this->assertInternalType('string',
            $this->searchDatabase->getConditions());
    }

    public function testSetAndGetConditionsWithoutConditions()
    {
        $this->searchDatabase->setConditions(array());
        $this->assertEquals('', $this->searchDatabase->getConditions());
    }

    public function testGetMatchClause()
    {
        $this->searchDatabase->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%')",
            $this->searchDatabase->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->searchDatabase->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoSearchTerms()
    {
        $this->searchDatabase->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%') OR (faqdata.author LIKE '%Rinne%')",
            $this->searchDatabase->getMatchClause('Thorsten Rinne'));
        $this->assertInternalType('string',
            $this->searchDatabase->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoColumns()
    {
        $this->searchDatabase->setMatchingColumns(array('faqdata.author', 'faqdata.thema'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%' OR faqdata.thema LIKE '%Thorsten%')",
            $this->searchDatabase->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->searchDatabase->getMatchClause('Thorsten'));
    }
}
