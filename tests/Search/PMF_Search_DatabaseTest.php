<?php
/**
 * Test case for PMF_Search_Database
 *
 * PHP Version 5.4
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
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

/**
 * Category test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-26
 */
class PMF_Search_DatabaseTest extends PHPUnit_Framework_TestCase
{
    private $PMF_Search_Database;

    private $PMF_Configuration;

    private $dbHandle;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $this->dbHandle = new PMF_DB_Sqlite3();
        $this->PMF_Configuration = new PMF_Configuration($this->dbHandle);
        $this->PMF_Search_Database = new PMF_Search_Database($this->PMF_Configuration);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Search_Database = null;
        parent::tearDown();
    }

    public function testSetAndGetTable()
    {
        $this->PMF_Search_Database->setTable('faqdata');
        $this->assertEquals('faqdata', $this->PMF_Search_Database->getTable());
        $this->assertInternalType('string', $this->PMF_Search_Database->getTable());
    }

    public function testSetAndGetTableWithoutTable()
    {
        $this->assertEquals('', $this->PMF_Search_Database->getTable());
    }

    public function testSetAndGetJoinedTable()
    {
        $this->PMF_Search_Database->setJoinedTable('faqcategoryrelations');
        $this->assertEquals(' LEFT JOIN faqcategoryrelations ON ', $this->PMF_Search_Database->getJoinedTable());
        $this->assertInternalType('string', $this->PMF_Search_Database->getJoinedTable());
    }

    public function testSetAndGetJoinedTableWithoutJoinedTable()
    {
        $this->assertEquals('', $this->PMF_Search_Database->getJoinedTable());
    }

    public function testSetAndGetResultColumns()
    {
        $resultColumns = array('faqdata.id AS id',
                               'faqdata.lang AS lang',
                               'faqdata.thema AS question',
                               'faqdata.content AS answer');

        $this->PMF_Search_Database->setResultColumns($resultColumns);
        $this->assertEquals('faqdata.id AS id, faqdata.lang AS lang, faqdata.thema AS question, faqdata.content AS answer',
            $this->PMF_Search_Database->getResultColumns());
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getResultColumns());
    }

    public function testSetAndGetResultColumnsWithoutResultColumns()
    {
        $this->PMF_Search_Database->setResultColumns(array());
        $this->assertEquals('', $this->PMF_Search_Database->getResultColumns());
    }

    public function testSetAndGetJoinedColumns()
    {
        $joinedColumns = array('faqdata.id = faqcategoryrelations.record_id',
                               'faqdata.lang = faqcategoryrelations.record_lang');

        $this->PMF_Search_Database->setJoinedColumns($joinedColumns);
        $this->assertEquals('faqdata.id = faqcategoryrelations.record_id AND faqdata.lang = faqcategoryrelations.record_lang ',
            $this->PMF_Search_Database->getJoinedColumns());
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getJoinedColumns());
    }

    public function testSetAndGetJoinedColumnsWithoutJoinedColumns()
    {
        $this->PMF_Search_Database->setJoinedColumns(array());
        $this->assertEquals('', $this->PMF_Search_Database->getJoinedColumns());
    }

    public function testSetAndGetMatchingColumns()
    {
        $matchingColumns = array('faqdata.thema',
                                 'faqdata.content',
                                 'faqdata.keywords');

        $this->PMF_Search_Database->setMatchingColumns($matchingColumns);
        $this->assertEquals('faqdata.thema, faqdata.content, faqdata.keywords',
            $this->PMF_Search_Database->getMatchingColumns());
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchingColumns());
    }

    public function testSetAndGetMatchingColumnsWithoutColums()
    {
        $this->PMF_Search_Database->setMatchingColumns(array());
        $this->assertEquals('', $this->PMF_Search_Database->getMatchingColumns());
    }

    public function testSetAndGetConditions()
    {
        $conditions = array('faqdata.active' => "'yes'",
                            'faqcategoryrelations.category_id' => 1);

        $this->PMF_Search_Database->setConditions($conditions);
        $this->assertEquals(" AND faqdata.active = 'yes' AND faqcategoryrelations.category_id = 1",
            $this->PMF_Search_Database->getConditions());
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getConditions());
    }

    public function testSetAndGetConditionsWithoutConditions()
    {
        $this->PMF_Search_Database->setConditions(array());
        $this->assertEquals('', $this->PMF_Search_Database->getConditions());
    }

    public function testGetMatchClause()
    {
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%')",
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoSearchTerms()
    {
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%') OR (faqdata.author LIKE '%Rinne%')",
            $this->PMF_Search_Database->getMatchClause('Thorsten Rinne'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoColumns()
    {
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author', 'faqdata.thema'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%' OR faqdata.thema LIKE '%Thorsten%')",
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }
}
