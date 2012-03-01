<?php
/**
 * Test case for PMF_Search_Database
 * 
 * PHP Version 5.2
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Abstract.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Interface.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Database/Sqlite.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_DB/Driver.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_DB/Sqlite.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/Exception.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/Language.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/String.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_String/Abstract.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_String/Mbstring.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_String/UTF8ToLatinConvertable.php';

/**
 * PMF_Category test case
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-26
 */
class PMF_Search_DatabaseTest extends PHPUnit_Framework_TestCase
{
    private $PMF_Search_Database;
    
    private $PMF_Language;
    
    private $dbHandle;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        PMF_String::init('en');
        
        $this->PMF_Language        = new PMF_Language();
        $this->PMF_Search_Database = new PMF_Search_Database($this->PMF_Language);
        $this->dbHandle            = new PMF_DB_Sqlite();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Search_Database = null;
        parent::tearDown();
    }
    
    public function testSetDatabaseHandle()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->assertEquals(new PMF_DB_Sqlite(), $this->PMF_Search_Database->getDatabaseHandle());
    }
    
    public function testSetDatabaseHandleWrongParameter()
    {
        $this->setExpectedException('PMF_Exception');
        try {
            $this->PMF_Search_Database->setDatabaseHandle('wrongParameter');
        } catch (Exception $expected) {
            throw new PMF_Exception($expected);
        }
    }
    
    public function testGetDatabaseHandleType()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->assertInstanceOf('PMF_DB_Sqlite', $this->PMF_Search_Database->getDatabaseHandle());
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
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%')", 
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }
    
    public function testGetMatchClauseWithTwoSearchTerms()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%') OR (faqdata.author LIKE '%Rinne%')", 
            $this->PMF_Search_Database->getMatchClause('Thorsten Rinne'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }
    
    public function testGetMatchClauseWithTwoColumns()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->PMF_Search_Database->setMatchingColumns(array('faqdata.author', 'faqdata.thema'));
        $this->assertEquals(" (faqdata.author LIKE '%Thorsten%' OR faqdata.thema LIKE '%Thorsten%')", 
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
        $this->assertInternalType('string',
            $this->PMF_Search_Database->getMatchClause('Thorsten'));
    }
}