<?php
/**
 * Test case for PMF_Search_Database
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-06-06
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Search\SearchDatabase;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchDatabaseTest
 */
class SearchDatabaseTest extends TestCase
{
    /** @var SearchDatabase */
    private $searchDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);
        $this->searchDatabase = new SearchDatabase($configuration);
    }

    protected function tearDown(): void
    {
        $this->searchDatabase = null;
        parent::tearDown();
    }

    public function testSetAndGetTable()
    {
        $this->searchDatabase->setTable('faqdata');
        $this->assertEquals('faqdata', $this->searchDatabase->getTable());
        $this->assertIsString($this->searchDatabase->getTable());
    }

    public function testSetAndGetTableWithoutTable()
    {
        $this->assertEquals('', $this->searchDatabase->getTable());
    }

    public function testSetAndGetJoinedTable()
    {
        $this->searchDatabase->setJoinedTable('faqcategoryrelations');
        $this->assertEquals(' LEFT JOIN faqcategoryrelations ON ', $this->searchDatabase->getJoinedTable());
        $this->assertIsString($this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetJoinedTableWithoutJoinedTable()
    {
        $this->assertEquals('', $this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetResultColumns()
    {
        $resultColumns = [
            'faqdata.id AS id',
            'faqdata.lang AS lang',
            'faqdata.thema AS question',
            'faqdata.content AS answer'
        ];

        $this->searchDatabase->setResultColumns($resultColumns);
        $this->assertEquals('faqdata.id AS id, faqdata.lang AS lang, faqdata.thema AS question, faqdata.content AS answer',
            $this->searchDatabase->getResultColumns());
        $this->assertIsString($this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetResultColumnsWithoutResultColumns()
    {
        $this->searchDatabase->setResultColumns([]);
        $this->assertEquals('', $this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetJoinedColumns()
    {
        $joinedColumns = [
            'faqdata.id = faqcategoryrelations.record_id',
            'faqdata.lang = faqcategoryrelations.record_lang'
        ];

        $this->searchDatabase->setJoinedColumns($joinedColumns);
        $this->assertEquals('faqdata.id = faqcategoryrelations.record_id AND faqdata.lang = faqcategoryrelations.record_lang ',
            $this->searchDatabase->getJoinedColumns());
        $this->assertIsString($this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetJoinedColumnsWithoutJoinedColumns()
    {
        $this->searchDatabase->setJoinedColumns([]);
        $this->assertEquals('', $this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetMatchingColumns()
    {
        $matchingColumns = [
            'faqdata.thema',
            'faqdata.content',
            'faqdata.keywords'
        ];

        $this->searchDatabase->setMatchingColumns($matchingColumns);
        $this->assertEquals('faqdata.thema, faqdata.content, faqdata.keywords',
            $this->searchDatabase->getMatchingColumns());
        $this->assertIsString($this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetMatchingColumnsWithoutColums()
    {
        $this->searchDatabase->setMatchingColumns([]);
        $this->assertEquals('', $this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetConditions()
    {
        $conditions = [
            'faqdata.active' => "'yes'",
            'faqcategoryrelations.category_id' => 1
        ];

        $this->searchDatabase->setConditions($conditions);
        $this->assertEquals(" AND faqdata.active = 'yes' AND faqcategoryrelations.category_id = 1",
            $this->searchDatabase->getConditions());
        $this->assertIsString($this->searchDatabase->getConditions());
    }

    public function testSetAndGetConditionsWithoutConditions()
    {
        $this->searchDatabase->setConditions([]);
        $this->assertEquals('', $this->searchDatabase->getConditions());
    }

    public function testGetMatchClause()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%')",
            $this->searchDatabase->getMatchClause('Thorsten'));
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoSearchTerms()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%') OR (faqdata.author LIKE '%Rinne%')",
            $this->searchDatabase->getMatchClause('Thorsten Rinne'));
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoColumns()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author', 'faqdata.thema']);
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%' OR faqdata.thema LIKE '%Thorsten%')",
            $this->searchDatabase->getMatchClause('Thorsten'));
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }
}
